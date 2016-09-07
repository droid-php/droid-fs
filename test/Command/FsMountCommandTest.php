<?php

namespace Droid\Test\Plugin\Fs\Command;

use RuntimeException;

use org\bovigo\vfs\vfsStream;
use PHPUnit_Framework_TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

use Droid\Plugin\Fs\Command\FsMountCommand;
use Droid\Plugin\Fs\Model\File\FileFactory;
use Droid\Plugin\Fs\Model\File\LineFactory;
use Droid\Plugin\Fs\Model\Fstab\Fstab;
use Droid\Plugin\Fs\Model\Fstab\FstabException;
use Droid\Plugin\Fs\Model\FsMount;

class FsMountCommandTest extends PHPUnit_Framework_TestCase
{
    protected $app;
    protected $fileFactory;
    protected $fsMount;
    protected $fstab;
    protected $fstabFile;
    protected $lineFac;
    protected $tester;
    protected $vfs;

    protected function setUp()
    {
        $this->fsMount = $this
            ->getMockBuilder(FsMount::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;
        $this->lineFac = $this
            ->getMockBuilder(LineFactory::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;
        $this->fileFactory = $this
            ->getMockBuilder(FileFactory::class)
            ->setConstructorArgs(array(Fstab::class, $this->lineFac))
            ->getMock()
        ;
        $this->fstab = $this->getMockForFstab();
        $this
            ->fileFactory
            ->method('makeFile')
            ->willReturn($this->fstab)
        ;

        $command = new FsMountCommand($this->fileFactory, $this->fsMount);

        $this->app = new Application;
        $this->app->add($command);

        $this->tester = new CommandTester($command);

        $this->vfs = vfsStream::setup('etc');
        $this->fstabFile = vfsStream::newFile('fstab')->at($this->vfs);
    }

    private function getMockForFstab()
    {
        $mock = $this
            ->getMockBuilder(Fstab::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $mock
            ->method('addEntry')
            ->willReturnSelf()
        ;
        $mock
            ->method('backup')
            ->willReturnSelf()
        ;

        return $mock;
    }

    protected function tearDown()
    {
        clearstatcache();
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage I cannot add an entry to the fstab file
     */
    public function testCommandWithUnusableFstabFileWillThrowException()
    {
        $this
            ->fstab
            ->method('addEntry')
            ->willThrowException(new FstabException)
        ;

        $this->tester->execute(array(
            'command' => $this->app->find('fs:mount')->getName(),
            'filesystem' => '/dev/sda1',
            'mount-point' => '/mnt/point',
            'type' => 'ext3',
            '--fstab' => vfsStream::url('etc/not_a_file'),
        ));
    }

    public function testCommandWithNewFstabInfoInCheckModeWillNotMakeChanges()
    {
        $this
            ->fstab
            ->method('changed')
            ->willReturn(true)
        ;

        $this
            ->fstab
            ->expects($this->never())
            ->method('finish')
        ;
        $this
            ->fsMount
            ->expects($this->never())
            ->method('mounted')
        ;

        $this->tester->execute(array(
            'command' => $this->app->find('fs:mount')->getName(),
            'filesystem' => '/dev/sda1',
            'mount-point' => '/mnt/point',
            'type' => 'ext3',
            '--fstab' => $this->fstabFile->url(),
            '--check' => true,
        ));

        $this->assertRegExp(
            '/"changed":\s?true/',
            $this->tester->getDisplay()
        );
    }

    public function testCommandWithExistingFstabInfoWillNotMakeChangesToTheFstabFile()
    {
        $this
            ->fstab
            ->method('changed')
            ->willReturn(false)
        ;
        $this
            ->fsMount
            ->method('mounted')
            ->willReturn(true)
        ;

        $this
            ->fstab
            ->expects($this->never())
            ->method('finish')
        ;

        $this->tester->execute(array(
            'command' => $this->app->find('fs:mount')->getName(),
            'filesystem' => '/dev/sda1',
            'mount-point' => '/mnt/point',
            'type' => 'ext3',
            '--fstab' => $this->fstabFile->url(),
        ));

        $this->assertRegExp(
            '/Nothing to do/',
            $this->tester->getDisplay()
        );
    }

    public function testCommandWithNewFstabInfoWillMakeChangesToTheFstabFile()
    {
        $this
            ->fstab
            ->method('changed')
            ->willReturn(true)
        ;
        $this
            ->fsMount
            ->method('mounted')
            ->willReturn(false)
        ;
        $this
            ->fsMount
            ->method('mount')
            ->willReturn(true)
        ;

        $this
            ->fstab
            ->expects($this->once())
            ->method('backup')
        ;
        $this
            ->fstab
            ->expects($this->once())
            ->method('finish')
        ;

        $this->tester->execute(array(
            'command' => $this->app->find('fs:mount')->getName(),
            'filesystem' => '/dev/sda1',
            'mount-point' => '/mnt/point',
            'type' => 'ext3',
            '--fstab' => $this->fstabFile->url(),
        ));

        $this->assertRegExp(
            '/I am making your changes to the fstab file/',
            $this->tester->getDisplay()
        );
    }

    public function testCommandWithNewFstabInfoAndSkipMountArgWillNotMount()
    {
        $this
            ->fstab
            ->method('changed')
            ->willReturn(true)
        ;

        $this
            ->fsMount
            ->expects($this->never())
            ->method('mounted')
        ;

        $this->tester->execute(array(
            'command' => $this->app->find('fs:mount')->getName(),
            'filesystem' => '/dev/sda1',
            'mount-point' => '/mnt/point',
            'type' => 'ext3',
            '--fstab' => $this->fstabFile->url(),
            '--skip-mount' => true,
        ));

        $this->assertRegExp(
            '/I am not mounting/',
            $this->tester->getDisplay()
        );
    }

    public function testCommandWithExistingFstabInfoAndSkipMountArgWillNotMount()
    {
        $this
            ->fstab
            ->method('changed')
            ->willReturn(false)
        ;

        $this
            ->fsMount
            ->expects($this->never())
            ->method('mounted')
        ;

        $this->tester->execute(array(
            'command' => $this->app->find('fs:mount')->getName(),
            'filesystem' => '/dev/sda1',
            'mount-point' => '/mnt/point',
            'type' => 'ext3',
            '--fstab' => $this->fstabFile->url(),
            '--skip-mount' => true,
        ));

        $this->assertRegExp(
            '/I am not mounting/',
            $this->tester->getDisplay()
        );
    }

    public function testCommandWithChangeToMountedFilesystemWillAttemptUmount()
    {
        $this
            ->fstab
            ->method('changed')
            ->willReturn(true)
        ;

        $this
            ->fsMount
            ->expects($this->once())
            ->method('mounted')
            ->with($this->equalTo('/mnt/point'))
            ->willReturn(true)
        ;
        $this
            ->fsMount
            ->expects($this->once())
            ->method('umount')
            ->with($this->equalTo('/mnt/point'))
            ->willReturn(true)
        ;

        $this->tester->execute(array(
            'command' => $this->app->find('fs:mount')->getName(),
            'filesystem' => '/dev/sda1',
            'mount-point' => '/mnt/point',
            'type' => 'ext3',
            '--fstab' => $this->fstabFile->url(),
        ));

        $this->assertRegExp(
            '/I attempt a umount/',
            $this->tester->getDisplay()
        );
    }

    public function testCommandWithChangeToMountedFilesystemWillAttemptMount()
    {
        $this
            ->fstab
            ->method('changed')
            ->willReturn(true)
        ;

        $this
            ->fsMount
            ->expects($this->once())
            ->method('mounted')
            ->with($this->equalTo('/mnt/point'))
            ->willReturn(true)
        ;
        $this
            ->fsMount
            ->expects($this->once())
            ->method('umount')
            ->with($this->equalTo('/mnt/point'))
            ->willReturn(true)
        ;
        $this
            ->fsMount
            ->expects($this->once())
            ->method('mount')
            ->with($this->equalTo('/mnt/point'))
            ->willReturn(true)
        ;

        $this->tester->execute(array(
            'command' => $this->app->find('fs:mount')->getName(),
            'filesystem' => '/dev/sda1',
            'mount-point' => '/mnt/point',
            'type' => 'ext3',
            '--fstab' => $this->fstabFile->url(),
        ));

        $this->assertRegExp(
            '/I attempt a umount/',
            $this->tester->getDisplay()
        );
    }
}
