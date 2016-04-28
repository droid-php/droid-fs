<?php
namespace Droid\Plugin\Fs\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Droid\Plugin\Fs\Utils;
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
                'source filename or directory'
            )
            ->addArgument(
                'dst',
                InputArgument::REQUIRED,
                'destination filename/directory'
            )
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $src = $input->getArgument('src');
        $dst = $input->getArgument('dst');
        
        $output->WriteLn("Fs Rename From: $src  to $dst ");

        if (is_dir($src)) {
            $type = 'Directory';
        } else {
            $type = 'File';
        }
        if (!file_exists($src)) {
            throw new RuntimeException("Source ".$type." not exists: " . $src);
        }
        if (!rename($src, $dst)) {
            throw new RuntimeException("Rename failed: ".$type.' to ' .$src);
        }
    }
}
