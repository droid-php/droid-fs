<?php
namespace Droid\Plugin\Fs\Command;

use RuntimeException;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Droid\Lib\Plugin\Command\CheckableTrait;
use Droid\Plugin\Fs\Utils;

class FsTouchCommand extends Command
{
    use CheckableTrait;

    public function configure()
    {
        $this
            ->setName('fs:touch')
            ->setDescription('Change file access and modification time')
            ->addArgument(
                'file',
                InputArgument::REQUIRED,
                'Path to file'
            )
            ->addOption(
                'no-create',
                null,
                InputOption::VALUE_NONE,
                'Do not create a file'
            )
            ->addOption(
                'atime',
                'a',
                InputOption::VALUE_REQUIRED,
                'Set the access time'
            )
            ->addOption(
                'mtime',
                'm',
                InputOption::VALUE_REQUIRED,
                'Set the modification time'
            )
        ;
        $this->configureCheckMode();
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->activateCheckMode($input);

        $path = Utils::normalizePath($input->getArgument('file'));
        $exists = file_exists($path);

        $opt_atime = $input->getOption('atime');
        $opt_mtime = $input->getOption('mtime');

        if ($exists && ! $opt_atime && ! $opt_mtime) {
            $output->writeln(
                sprintf(
                    'The file "%s" exists and I was not instructed to change a timestamp. Nothing to do.',
                    $path
                )
            );
            $this->reportChange($output);
            return 0;
        } elseif (! $exists && $input->getOption('no-create')) {
            $output->writeln(
                sprintf(
                    'The file "%s" does not exist and I was instructed not to create it. Nothing to do.',
                    $path
                )
            );
            $this->reportChange($output);
            return 0;
        }

        $now = new \DateTime;

        $atime = null;
        if ($opt_atime && strtolower($opt_atime) == 'now') {
            $atime = $now->getTimestamp();
        } elseif ($opt_atime) {
            try {
                $atime = new \DateTime($opt_atime);
                $atime = $atime->getTimestamp();
            } catch (\Exception $e) {
                throw new RuntimeException(
                    sprintf(
                        'I do not like the look of your atime: %s.',
                        $e->getMessage()
                    )
                );
            }
        }
        $mtime = null;
        if ($opt_mtime && strtolower($opt_mtime) == 'now') {
            $mtime = $now->getTimestamp();
        } elseif ($opt_mtime) {
            try {
                $mtime = new \DateTime($opt_mtime);
                $mtime = $mtime->getTimestamp();
            } catch (\Exception $e) {
                throw new RuntimeException(
                    sprintf(
                        'I do not like the look of your mtime: %s.',
                        $e->getMessage()
                    )
                );
            }
        }

        if (! $exists) {
            $this->markChange();
        } else {
            $stat = stat($path);
            if ($stat === false) {
                throw new RuntimeException(
                    sprintf('Cannot stat file "%s".', $path)
                );
            }
            if ($atime && $atime !== $stat['atime']) {
                $this->markChange();
            } elseif (!$atime) {
                // preserve atime
                $atime = $stat['atime'];
            }
            if ($mtime && $mtime !== $stat['mtime']) {
                $this->markChange();
            } elseif (!$mtime) {
                // preserve mtime
                $mtime = $stat['mtime'];
            }
            if (!$this->wouldChange()) {
                $output->writeln(
                    sprintf(
                        'The file "%s" already has the supplied timestamps. Nothing to do.',
                        $path
                    )
                );
                $this->reportChange($output);
                return 0;
            }
            $this->markChange();
        }

        if ($this->checkMode()) {
            $this->reportChange($output);
            return 0;
        }

        $touched = touch($path, $mtime, $atime);
        if (!$touched) {
            throw new RuntimeException(
                sprintf('Failed to touch file "%s".', $path)
            );
        }

        $output->writeln(sprintf('Touched file "%s".', $path));
        $this->reportChange($output);
    }
}
