<?php

namespace Droid\Plugin\Fs;

use Symfony\Component\Process\ProcessBuilder;

use Droid\Plugin\Fs\Command\FsChownCommand;
use Droid\Plugin\Fs\Service\PosixAclObjectLookup;

class DroidPlugin
{
    public function __construct($droid)
    {
        $this->droid = $droid;
    }

    public function getCommands()
    {
        $commands = [];

        $commands[] = new \Droid\Plugin\Fs\Command\FsChmodCommand();
        $commands[] = new FsChownCommand(
            new PosixAclObjectLookup,
            new ProcessBuilder
        );
        $commands[] = new \Droid\Plugin\Fs\Command\FsCopyCommand();
        $commands[] = new \Droid\Plugin\Fs\Command\FsMkdirCommand();
        $commands[] = new \Droid\Plugin\Fs\Command\FsMountCommand();
        $commands[] = new \Droid\Plugin\Fs\Command\FsRenameCommand();
        $commands[] = new \Droid\Plugin\Fs\Command\FsTemplateCommand();
        $commands[] = new \Droid\Plugin\Fs\Command\FsTouchCommand();
        return $commands;
    }
}
