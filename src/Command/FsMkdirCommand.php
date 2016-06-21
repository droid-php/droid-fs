<?php

namespace Droid\Plugin\Fs\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Droid\Lib\Plugin\Command\CheckableTrait;
use Droid\Plugin\Fs\Utils;
use RuntimeException;

class FsMkdirCommand extends Command
{
    use CheckableTrait;

    public function configure()
    {
        $this->setName('fs:mkdir')
            ->setDescription('Creates a directory')
            ->addArgument(
                'directory',
                InputArgument::REQUIRED,
                'Directory name to create'
            )
            ->addOption(
                'mode',
                'm',
                InputOption::VALUE_REQUIRED,
                'Permission filemode'
            )
            ->addOption(
                'force',
                'f',
                InputOption::VALUE_NONE,
                'Force'
            )
        ;
        $this->configureCheckMode();
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->activateCheckMode($input);
        $directory = $input->getArgument('directory');
        $directory = Utils::normalizePath($directory);
        $output->writeLn("Fs mkdir: $directory");
        if (!$input->getOption('force')) {
            if (file_exists($directory)) {
                throw new RuntimeException("Directory already exists: " . $directory);
            }
        }
        $mode = $input->getOption('mode');
        if (!$mode) {
            $mode = 0777;
        } else {
            $mode = octdec($mode);
        }
        $this->markChange();
        if (!$this->checkMode()) {
            @mkdir($directory, $mode, true);
            if (!file_exists($directory)) {
                throw new RuntimeException("Directory creation failed: " . $directory);
            }
        }
        $this->reportChange($output);
    }
}
