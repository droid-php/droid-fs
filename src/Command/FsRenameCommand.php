<?php
namespace Droid\Plugin\Fs\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Droid\Lib\Plugin\Command\CheckableTrait;
use RuntimeException;

class FsRenameCommand extends Command
{
    use CheckableTrait;

    public function configure()
    {
        $this->setName('fs:rename')
            ->setDescription('Renames a file/directory')
            ->addArgument(
                'src',
                InputArgument::REQUIRED,
                'Source filename or directory'
            )
            ->addArgument(
                'dest',
                InputArgument::REQUIRED,
                'Destination filename or directory'
            );
        $this->configureCheckMode();
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->activateCheckMode($input);
        $src = $input->getArgument('src');
        $dest = $input->getArgument('dest');

        $output->WriteLn("Fs Rename From: $src  to $dest ");

        if (is_dir($src)) {
            $type = 'Directory';
        } else {
            $type = 'File';
        }
        if (!file_exists($src)) {
            throw new RuntimeException("Source does not exist: " . $src);
        }
        if (file_exists($dest)) {
            throw new RuntimeException("Destination already exist: " . $dest);
        }
        $this->markChange();
        if (!$this->checkMode() && !rename($src, $dest)) {
            throw new RuntimeException("Rename failed: " . $src .' to ' .$dest);
        }
        $this->reportChange($output);
    }
}
