<?php
namespace Droid\Plugin\Fs\Command;

use RuntimeException;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\ProcessBuilder;

use Droid\Lib\Plugin\Command\CheckableTrait;
use Droid\Plugin\Fs\Utils;
use Droid\Plugin\Fs\Service\AclObjectLookupInterface;

class FsChownCommand extends Command
{
    use CheckableTrait;

    protected $processBuilder;
    protected $userLookup;

    public function __construct(
        AclObjectLookupInterface $userLookup,
        ProcessBuilder $processBuilder,
        $name = null
    ) {
        $this->userLookup = $userLookup;
        $this->processBuilder = $processBuilder;
        return parent::__construct($name);
    }

    public function configure()
    {
        $this
            ->setName('fs:chown')
            ->setDescription('Change ownership of a file')
            ->addArgument(
                'file',
                InputArgument::REQUIRED,
                'Path to file'
            )
            ->addArgument(
                'user',
                InputArgument::REQUIRED,
                'User name'
            )
            ->addArgument(
                'group',
                InputArgument::REQUIRED,
                'Group name'
            )
        ;
        $this->configureCheckMode();
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->activateCheckMode($input);

        $path = Utils::normalizePath($input->getArgument('file'));

        if (! file_exists($path)) {
            throw new RuntimeException(
                sprintf('The file "%s" does not exist.', $path)
            );
        }

        $user = $input->getArgument('user');
        $uid = $this->userLookup->userId($user);
        if ($uid === null) {
            throw new RuntimeException(
                sprintf(
                    'Failed to determine user id for a user named "%s". Do they exist?',
                    $user
                )
            );
        }

        $group = $input->getArgument('group');
        $gid = $this->userLookup->groupId($group);
        if ($gid === null) {
            throw new RuntimeException(
                sprintf(
                    'Failed to determine group id for a group named "%s". Does it exist?',
                    $group
                )
            );
        }

        $stat = stat($path);
        if ($stat === false) {
            throw new RuntimeException(
                sprintf('Cannot stat file "%s".', $path)
            );
        }
        if ($uid === $stat['uid'] && $gid === $stat['gid']) {
            $output->writeln(
                sprintf(
                    'The file "%s" already has the supplied ownership. Nothing to do.',
                    $path
                )
            );
            $this->reportChange($output);
            return 0;
        }

        $this->markChange();

        if ($uid === $stat['uid']) {
            $output->writeln(
                sprintf(
                    'The file "%s" is already owned by user "%s". I will not change user ownership.',
                    $path,
                    $user
                )
            );
        }
        if ($gid === $stat['gid']) {
            $output->writeln(
                sprintf(
                    'The file "%s" is already owned by group "%s". I will not change group ownership.',
                    $path,
                    $group
                )
            );
        }

        if ($this->checkMode()) {
            $output->writeln(sprintf('I would chown file "%s".', $path));
        } else {
            $this->chown($user, $group, $path);
            $output->writeln(sprintf('Chowned file "%s".', $path));
        }

        $this->reportChange($output);
    }

    private function chown($user, $group, $path)
    {
        if ($this->checkMode()) {
            return;
        }
        $p = $this->getProcess(
            array(
                'sudo', #?
                'chown',
                sprintf('%s:%s', $user, $group),
                $path
            )
        );
        if ($p->run()) {
            throw new RuntimeException(
                sprintf(
                    'Failed to change ownership of file "%s": %s',
                    $path,
                    trim($p->getErrorOutput())
                ),
                $p->getExitCode()
            );
        }
    }

    private function getProcess($arguments)
    {
        return $this
            ->processBuilder
            ->setArguments($arguments)
            ->setTimeout(0.0)
            ->getProcess()
        ;
    }
}
