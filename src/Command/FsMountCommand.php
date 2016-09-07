<?php
namespace Droid\Plugin\Fs\Command;

use RuntimeException;

use Droid\Lib\Plugin\Command\CheckableTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Droid\Plugin\Fs\Model\File\FileFactory;
use Droid\Plugin\Fs\Model\FsMount;
use Droid\Plugin\Fs\Model\Fstab\FstabException;

class FsMountCommand extends Command
{
    use CheckableTrait;

    protected $fileFactory;
    protected $fsMount;

    public function __construct(
        FileFactory $fileFactory,
        FsMount $fsMount,
        $name = null
    ) {
        $this->fileFactory = $fileFactory;
        $this->fsMount = $fsMount;
        return parent::__construct($name);
    }

    public function configure()
    {
        $this->setName('fs:mount')
            ->setDescription('Mount a filesystem by updating /etc/fstab')
            ->addArgument(
                'filesystem',
                InputArgument::REQUIRED,
                'Source filename'
            )
            ->addArgument(
                'mount-point',
                InputArgument::REQUIRED,
                'Mount-pount, for example /mnt/my-fs'
            )
            ->addArgument(
                'type',
                InputArgument::REQUIRED,
                'Filesystem type, for example: ext3, proc, swap, nfs'
            )
            ->addOption(
                'fstab',
                null,
                InputOption::VALUE_REQUIRED,
                'Name of the fstab filename, defaults to /etc/fstab',
                '/etc/fstab'
            )
            ->addOption(
                'options',
                'o',
                InputOption::VALUE_REQUIRED,
                'Mount options',
                'defaults'
            )
            ->addOption(
                'dump',
                'd',
                InputOption::VALUE_REQUIRED,
                'Dump frequency.'
            )
            ->addOption(
                'pass',
                'p',
                InputOption::VALUE_REQUIRED,
                'Pass number'
            )
            ->addOption(
                'skip-mount',
                null,
                InputOption::VALUE_NONE,
                'Update the fstab file, but do not perform the mount operation.'
            )
        ;
        $this->configureCheckMode();
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->activateCheckMode($input);

        $fstab = $this
            ->fileFactory
            ->makeFile($input->getOption('fstab'))
        ;
        try {
            $fstab->addEntry(
                $input->getArgument('filesystem'),
                $input->getArgument('mount-point'),
                $input->getArgument('type'),
                $input->getOption('options'),
                $input->getOption('dump'),
                $input->getOption('pass')
            );
        } catch (FstabException $e) {
            throw new RuntimeException(
                sprintf(
                    'I cannot add an entry to the fstab file "%s".',
                    $input->getOption('fstab')
                ),
                null,
                $e
            );
        }

        if ($fstab->changed()) {
            $this->markChange();
            if ($this->checkMode()) {
                $this->reportChange($output);
                return 0;
            }
            $output->WriteLn(
                sprintf(
                    'I am making your changes to the fstab file "%s".',
                    $input->getOption('fstab')
                )
            );
            $fstab
                ->backup($this->backupName($input->getOption('fstab')))
                ->finish()
            ;
        } else {
            $output->WriteLn(
                sprintf(
                    'I am not making any changes to the fstab file "%s"; it already has an entry for "%s %s".',
                    $input->getOption('fstab'),
                    $input->getArgument('filesystem'),
                    $input->getArgument('mount-point')
                )
            );
        }

        if ($input->getOption('skip-mount')) {
            $output->WriteLn('I am not mounting because you asked me to --skip-mount.');
            $this->reportChange($output);
            return;
        }

        $isMounted = $this->fsMount->mounted($input->getArgument('mount-point'));
        if ($isMounted && $fstab->changed()) {
            $output->WriteLn(
                sprintf(
                    'The mount info for "%s" is changed; I attempt a umount.',
                    $input->getArgument('mount-point')
                )
            );
            $this->fsMount->umount($input->getArgument('mount-point'));
        } elseif ($isMounted) {
            $output->WriteLn(
                sprintf(
                    'The mount info for "%s" is unchanged and already mounted. Nothing to do.',
                    $input->getArgument('mount-point')
                )
            );
            $this->reportChange($output);
            return 0;
        }

        $this->markChange();

        $output->WriteLn(
            sprintf(
                'I attempt to mount "%s".',
                $input->getArgument('mount-point')
            )
        );
        $this->fsMount->mount($input->getArgument('mount-point'));

        $this->reportChange($output);
    }

    private function backupName($originalName)
    {
        return sprintf('%s.%s.backup', $originalName, date('Y-m-d_H-i-s'));
    }
}
