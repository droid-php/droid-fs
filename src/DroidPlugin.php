<?php

namespace Droid\Plugin\Fs;

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
        $commands[] = new \Droid\Plugin\Fs\Command\FsCopyCommand();
        $commands[] = new \Droid\Plugin\Fs\Command\FsMkdirCommand();
        $commands[] = new \Droid\Plugin\Fs\Command\FsMountCommand();
        $commands[] = new \Droid\Plugin\Fs\Command\FsRenameCommand();
        $commands[] = new \Droid\Plugin\Fs\Command\FsTemplateCommand();
        return $commands;
    }
}
