<?php

namespace Droid\Plugin\Fs\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Droid\Plugin\Fs\Utils;
use RuntimeException;

class FsChmodCommand extends Command
{
    public function configure()
    {
        $this->setName('fs:chmod')
            ->setDescription('Modifies the file mode bits')
            ->addArgument(
                'mode',
                InputArgument::REQUIRED,
                'Permission filemode'
            )
            ->addArgument(
                'filename',
                InputArgument::REQUIRED,
                'filename to update'
            )
        ;
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $filename = $input->getArgument('filename');
        $filename = Utils::normalizePath($filename);

        $mode = $input->getArgument('mode');
        if (!$mode) {
            $mode = 0777;
        } else {
            $mode = octdec($mode);
        }

        if (!file_exists($filename)) {
            throw new RuntimeException("File does not exist: " . $filename);
        }

        $output->writeLn("Fs chmod: " . decoct($mode)  . " $filename");


        chmod($filename, $mode);

    }
}
