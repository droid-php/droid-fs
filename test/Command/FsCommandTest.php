<?php

namespace Droid\Test\Plugin\Fs\Command;

use org\bovigo\vfs\vfsStream;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

use Droid\Plugin\Fs\Command\FsChmodCommand;
use Droid\Plugin\Fs\Command\FsCopyCommand;
use Droid\Plugin\Fs\Command\FsMkdirCommand;
use Droid\Plugin\Fs\Command\FsRenameCommand;
use Droid\Plugin\Fs\Command\FsTemplateCommand;

class FsCommandTest extends \PHPUnit_Framework_TestCase
{
    protected $app;
    protected $vfs;

    protected function setUp()
    {
        $this->vfs = vfsStream::setup();
        $this->app = new Application;
    }

    public function testFsChmodCommandIsSane()
    {
        $command = new FsChmodCommand;
        $tester = new CommandTester($command);
        $this->app->add($command);

        vfsStream::newFile('some_file')->at($this->vfs)->setContent('hello!');

        $tester->execute(array(
            'command' => $this->app->find('fs:chmod')->getName(),
            'filename' => vfsStream::url('root/some_file'),
            'mode' => 0640
        ));
    }

    public function testFsCopyCommandIsSane()
    {
        $command = new FSCopyCommand;
        $tester = new CommandTester($command);
        $this->app->add($command);

        vfsStream::newFile('some_file')->at($this->vfs)->setContent('hello!');

        $tester->execute(array(
            'command' => $this->app->find('fs:copy')->getName(),
            'src' => vfsStream::url('root/some_file'),
            'dest' => vfsStream::url('root/copy_of_some_file')
        ));
    }

    public function testFsMkdirCommandIsSane()
    {
        $command = new FsMkdirCommand;
        $tester = new CommandTester($command);
        $this->app->add($command);

        vfsStream::newFile('some_file')->at($this->vfs)->setContent('hello!');

        $tester->execute(array(
            'command' => $this->app->find('fs:mkdir')->getName(),
            'directory' => vfsStream::url('root/new_dir')
        ));
    }

    public function testFsRenameCommandIsSane()
    {
        $command = new FsRenameCommand;
        $tester = new CommandTester($command);
        $this->app->add($command);

        vfsStream::newFile('some_file')->at($this->vfs)->setContent('hello!');

        $tester->execute(array(
            'command' => $this->app->find('fs:rename')->getName(),
            'src' => vfsStream::url('root/some_file'),
            'dest' => vfsStream::url('root/some_phile')
        ));
    }

    public function testFsTemplateCommandIsSane()
    {
        $command = new FsTemplateCommand;
        $tester = new CommandTester($command);
        $this->app->add($command);

        vfsStream::newFile('some_template')->at($this->vfs)->setContent('hello!');

        $tester->execute(array(
            'command' => $this->app->find('fs:template')->getName(),
            'src' => vfsStream::url('root/some_template'),
            'dest' => vfsStream::url('root/some_file')
        ));
    }
}
