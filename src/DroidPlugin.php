<?php

namespace Droid\Plugin\Fs;

use Symfony\Component\Process\ProcessBuilder;

use Droid\Plugin\Fs\Command\FsChownCommand;
use Droid\Plugin\Fs\Command\FsMountCommand;
use Droid\Plugin\Fs\Command\FsSetlineCommand;
use Droid\Plugin\Fs\Model\File\FileFactory;
use Droid\Plugin\Fs\Model\File\LineBasedFile;
use Droid\Plugin\Fs\Model\File\LineFactory;
use Droid\Plugin\Fs\Model\File\NameValueLine;
use Droid\Plugin\Fs\Model\FsMount;
use Droid\Plugin\Fs\Model\Fstab\Fstab;
use Droid\Plugin\Fs\Model\Fstab\FstabLine;
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
        $commands[] = new FsMountCommand(
            new FileFactory(Fstab::class, new LineFactory(FstabLine::class)),
            new FsMount(new ProcessBuilder)
        );
        $commands[] = new \Droid\Plugin\Fs\Command\FsRenameCommand();
        $commands[] = new FsSetlineCommand(
            new FileFactory(
                LineBasedFile::class,
                new LineFactory(NameValueLine::class)
            )
        );
        $commands[] = new \Droid\Plugin\Fs\Command\FsTemplateCommand();
        $commands[] = new \Droid\Plugin\Fs\Command\FsTouchCommand();
        return $commands;
    }
}
