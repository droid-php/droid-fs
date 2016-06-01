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
use LightnCandy\LightnCandy;

class FsTemplateCommand extends Command
{
    use CheckableTrait;

    public function configure()
    {
        $this->setName('fs:template')
            ->setDescription('Generates a file from a template')
            ->addArgument(
                'src',
                InputArgument::REQUIRED,
                'Source filename to update'
            )
            ->addArgument(
                'dest',
                InputArgument::REQUIRED,
                'Destination filename'
            )
            ->addOption(
                'json',
                'j',
                InputOption::VALUE_REQUIRED,
                'JSON filename to get data for in the template'
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

        $src = $input->getArgument('src');
        $content = Utils::getContents($src);

        $dest = $input->getArgument('dest');
        $dest = Utils::normalizePath($dest);

        $mode = $input->getOption('mode');
        if (!$mode) {
            $mode = 0644;
        } else {
            $mode = octdec($mode);
        }

        $this->markChange();

        if ($this->checkMode()) {
            $this->reportChange();
            return 0;
        }

        $output->writeLn("Creating file $dest. Mode: " . decoct($mode));

        $php = LightnCandy::compile($content);
        $render = LightnCandy::prepare($php);

        $data = [];
        $jsonFilename = $input->getOption('json');
        if ($jsonFilename) {
            $json = Utils::getContents($jsonFilename);
            $data = json_decode($json, true);
            if (!$data) {
                throw new RuntimeException("Can't parse data as JSON");
            }
        }

        $content = $render($data);

        file_put_contents($dest, $content);

        $this->reportChange($output);
    }
}
