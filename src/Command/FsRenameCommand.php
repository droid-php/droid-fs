<?php
namespace Droid\Plugin\Fs\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use RuntimeException;

class FsRenameCommand extends Command
{
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
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
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
        if (!rename($src, $dest)) {
            throw new RuntimeException("Rename failed: " . $src .' to ' .$dest);
        }
    }
}
