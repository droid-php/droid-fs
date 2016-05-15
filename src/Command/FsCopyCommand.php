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
            ->addOption(
                'check',
                'c',
                InputOption::VALUE_NONE,
                'Run in check mode'
            )
        ;
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $startStamp = microtime(true);
        $src = $input->getArgument('src');
        $dest = $input->getArgument('dest');
        $owner = $input->getOption('owner');
        $group = $input->getOption('group');
        $mask = $input->getOption('mask');
        $check = $input->getOption('check');
        
        $changed = false;

        //$output->WriteLn("Copying $src to $dest");
        if (substr($src, 0, 5)!='data:') {
            if (!file_exists($src)) {
                throw new RuntimeException("Source file does not exist: " . $src);
            }
        }
        
        $dirname = dirname($dest);
        if (!file_exists($dirname) && !in_array($dirname, ['.', '..'])) {
            throw new RuntimeException("Destination directory does not exist: " . $dirname);
        }
        
        if (!file_exists($dest)) {
            if ($output->getVerbosity() >= OutputInterface::VERBOSITY_DEBUG) {
                $output->writeln('Destination file does not yet exist');
            }
            $changed = true;
        } else {
            if (file_get_contents($src) != file_get_contents($dest)) {
                if ($output->getVerbosity() >= OutputInterface::VERBOSITY_DEBUG) {
                    $output->writeln('File contents differ');
                }
                $changed = true;
            }
        }
        
        if (!$check) {
            if (!copy($src, $dest)) {
                throw new RuntimeException("Copy failed: " . $src);
            }
        }

        if (file_exists($dest)) {
            if ($owner) {
                $currentOwner = posix_getpwuid(fileowner($dest))['name'];
                
                if ($currentOwner != $owner) {
                    if ($output->getVerbosity() >= OutputInterface::VERBOSITY_DEBUG) {
                        $output->writeln('File ownership differs');
                    }
                    $changed = true;

                    if (!$check) {
                        if (!chown($dest, $owner)) {
                            throw new RuntimeException("Failed to change owner: " . $owner);
                        } else {
                            $output->WriteLn("Changed owner: " . $owner);
                        }
                    }
                }
            }
            
            if ($group) {
                $currentGroup = posix_getgrgid(filegroup($dest))['name'];
                
                if ($currentGroup != $group) {
                    if ($output->getVerbosity() >= OutputInterface::VERBOSITY_DEBUG) {
                        $output->writeln('File group differs');
                    }
                    $changed = true;
                
                    if (!$check) {
                        if (!chgrp($dest, $group)) {
                            throw new RuntimeException("Failed to change group: " . $group);
                        }
                    }
                }
            }
            
            if ($mask) {
                $currentMask = fileperms($dest);
                $currentMask = substr(decoct($currentMask), -3);
                if ((int)$currentMask != (int)$mask) {
                    if ($output->getVerbosity() >= OutputInterface::VERBOSITY_DEBUG) {
                        $output->writeln('File mask differs');
                    }
                    $changed = true;
                    
                    if (!$check) {
                        if (!chmod($dest, octdec($mask))) {
                            throw new RuntimeException("Failed to change permission mask: " . $mask);
                        }
                    }
                }
            }
        }
        $res = [];
        $res['changed'] = $changed;
        $res['start'] = microtime(true);
        $res['end'] = microtime(true);
        $output->writeLn('[DROID-RESULT] ' . json_encode($res));
    }
}
