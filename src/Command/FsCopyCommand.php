<?php
namespace Droid\Plugin\Fs\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Droid\Plugin\Fs\Utils;
use RuntimeException;

class FSCopyCommand extends Command
{
    public function configure()
    {
        $this->setName('fs:copy')
            ->setDescription('Copy a single file')
            ->addArgument(
                'src',
                InputArgument::REQUIRED,
                'source filename'
            )
            ->addArgument(
                'dst',
                InputArgument::REQUIRED,
                'destination filename'
            )
            ->addOption(
                'owner',
                'o',
                InputOption::VALUE_REQUIRED,
                'name of the owner of the copied directory'
            )
            ->addOption(
                'group',
                'g',
                InputOption::VALUE_REQUIRED,
                'name of the group of the copied directory'
            )
            ->addOption(
                'mask',
                'm',
                InputOption::VALUE_REQUIRED,
                ' file permission mask (like 0640 - use octdec etc)'
            )
        ;
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $src = $input->getArgument('src');
        $dst = $input->getArgument('dst');
        $owner = $input->getOption('owner');
        $group = $input->getOption('group');
        $mask = $input->getOption('mask');

        $output->WriteLn("Fs copy From: $src  to $dst ");
        if (!file_exists($src)) {
            throw new RuntimeException("Source file not exists: " . $src);
        }
        $dirname = dirname($dst);
        if (!file_exists($dirname) && !in_array($dirname, ['.', '..'])) {
            throw new RuntimeException("Directory not exists: " . $dirname);
        }
        if (!copy($src, $dst)) {
            throw new RuntimeException("File copy failed: " . $src);
        }
        if ($owner) {
            if (!chown($dst, $owner)) {
                throw new RuntimeException("failed to change Owner: " . $owner);
            } else {
                $output->WriteLn("Change owner:". $owner);
            }
        }
        if ($group) {
            if (!chgrp($dst, $group)) {
                throw new RuntimeException("failed to chnage Group: " . $group);
            }
        }
        if ($mask) {
            if (!chmod($dst, octdec($mask))) {
                throw new RuntimeException("failed to chnage Permission: " . $group);
            }
        }
    }
}
