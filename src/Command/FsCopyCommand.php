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
                'Source filename'
            )
            ->addArgument(
                'dest',
                InputArgument::REQUIRED,
                'Destination filename'
            )
            ->addOption(
                'owner',
                'o',
                InputOption::VALUE_REQUIRED,
                'Name of the owner of the copied file'
            )
            ->addOption(
                'group',
                'g',
                InputOption::VALUE_REQUIRED,
                'Name of the group of the copied file'
            )
            ->addOption(
                'mask',
                'm',
                InputOption::VALUE_REQUIRED,
                'File permission mask (for example: 0640)'
            )
        ;
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $src = $input->getArgument('src');
        $dest = $input->getArgument('dest');
        $owner = $input->getOption('owner');
        $group = $input->getOption('group');
        $mask = $input->getOption('mask');

        //$output->WriteLn("Copying $src to $dest");
        if (substr($src, 0, 5)!='data:') {
            if (!file_exists($src)) {
                throw new RuntimeException("Source file not exists: " . $src);
            }
        }
        $dirname = dirname($dest);
        if (!file_exists($dirname) && !in_array($dirname, ['.', '..'])) {
            throw new RuntimeException("Destination directory does not exist: " . $dirname);
        }
        if (!copy($src, $dest)) {
            throw new RuntimeException("Copy failed: " . $src);
        }
        if ($owner) {
            if (!chown($dest, $owner)) {
                throw new RuntimeException("Failed to change owner: " . $owner);
            } else {
                $output->WriteLn("Changed owner: " . $owner);
            }
        }
        if ($group) {
            if (!chgrp($dest, $group)) {
                throw new RuntimeException("Failed to change group: " . $group);
            }
        }
        if ($mask) {
            if (!chmod($dest, octdec($mask))) {
                throw new RuntimeException("Failed to change permission mask: " . $mask);
            }
        }
    }
}
