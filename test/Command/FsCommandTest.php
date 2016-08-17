<?php

namespace Droid\Test\Plugin\Fs\Command;

use org\bovigo\vfs\vfsStream;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

use Droid\Plugin\Fs\Command\FsChmodCommand;
use Droid\Plugin\Fs\Command\FsCopyCommand;
use Droid\Plugin\Fs\Command\FsMkdirCommand;
use Droid\Plugin\Fs\Command\FsMountCommand;
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

    /**
     * @expectedException \Symfony\Component\Process\Exception\ProcessFailedException
     *
     * We are not mocking Process and so this command is executing `mount` for
     * real.  It would be better not to execute mount, especially if this test
     * were doing more than sanity checking the command, but it's not, so we
     * are, so there.
     */
    public function testFsMountCommandIsSane()
    {
        $command = new FsMountCommand;
        $tester = new CommandTester($command);
        $this->app->add($command);

        vfsStream::create(
            array(
                'etc' => array('fstab' => '# /etc/fstab: static file system information.'),
                'mnt' => array(),
                'dev' => array('cdrom' => array())
            )
        );

        $tester->execute(array(
            'command' => $this->app->find('fs:mount')->getName(),
            'filesystem' => vfsStream::url('root/dev/crdrom'),
            'mount-point' => vfsStream::url('root/mnt/crdrom'),
            'type' => 'iso9660',
            '--fstab' => vfsStream::url('root/etc/fstab'),
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
