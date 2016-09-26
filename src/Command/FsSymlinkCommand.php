<?php
namespace Droid\Plugin\Fs\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Droid\Lib\Plugin\Command\CheckableTrait;
use RuntimeException;

class FsSymlinkCommand extends Command
{
    use CheckableTrait;

    public function configure()
    {
        $this->setName('fs:symlink')
            ->setDescription('Make a symbolic link')
            ->addArgument(
                'src',
                InputArgument::REQUIRED,
                'Source'
            )
            ->addArgument(
                'dest',
                InputArgument::REQUIRED,
                'Destination filename'
            )
        ;
        $this->configureCheckMode();
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $src = $input->getArgument('src');
        $dest = $input->getArgument('dest');
        $this->activateCheckMode($input);

        if (!file_exists($dest)) {
            if ($output->getVerbosity() >= OutputInterface::VERBOSITY_DEBUG) {
                $output->writeln('Destination does not yet exist');
            }
            $this->markChange();

            if (!$this->checkMode()) {
                if (!symlink($src, $dest)) {
                    throw new RuntimeException("Symlink failed: " . $src . ' to ' . $dest);
                }
            }
        }
        $this->reportChange($output);
    }
}
