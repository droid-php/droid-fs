<?php

namespace Droid\Test\Plugin\Fs\Model;

use PHPUnit_Framework_TestCase;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\ProcessBuilder;

use Droid\Plugin\Fs\Model\FsMount;

class FsMountTest extends PHPUnit_Framework_TestCase
{
    protected $process;
    protected $processBuilder;

    protected function setUp()
    {
        $this->process = $this
            ->getMockBuilder(Process::class)
            ->disableOriginalConstructor()
            ->setMethods(array('run', 'getOutput', 'getErrorOutput', 'getExitCode'))
            ->getMock()
        ;
        $this->processBuilder = $this
            ->getMockBuilder(ProcessBuilder::class)
            ->setMethods(array('setArguments', 'setTimeout', 'getProcess'))
            ->getMock()
        ;
        $this->processBuilder
            ->method('getProcess')
            ->willReturn($this->process)
        ;
        $this->processBuilder
            ->method('setArguments')
            ->willReturnSelf()
        ;
        $this->processBuilder
            ->method('setTimeout')
            ->willReturnSelf()
        ;
    }

    /**
     * @covers \Droid\Plugin\Fs\Model\FsMount::mounted
     * @expectedException InvalidArgumentException
     */
    public function testMountedWithInvalidArgumentWillThrowException()
    {
        $fsMount = new FsMount($this->processBuilder);

        $fsMount->mounted(0);
    }

    /**
     * @covers \Droid\Plugin\Fs\Model\FsMount::mounted
     */
    public function testMountedWithUnmountedFilesystemWillReturnFalse()
    {
        $fsMount = new FsMount($this->processBuilder);

        $this
            ->process
            ->method('run')
            ->willReturn(1)
        ;
        $fsMount->mounted('/mnt/point');
    }

    /**
     * @covers \Droid\Plugin\Fs\Model\FsMount::__construct
     * @covers \Droid\Plugin\Fs\Model\FsMount::mounted
     * @covers \Droid\Plugin\Fs\Model\FsMount::getProcess
     */
    public function testMountedWithMountedFilesystemWillReturnTrue()
    {
        $fsMount = new FsMount($this->processBuilder);

        $this
            ->process
            ->method('run')
            ->willReturn(0)
        ;

        $this
            ->processBuilder
            ->expects($this->once())
            ->method('setArguments')
            ->with($this->equalTo(array('mountpoint', '-q', '/mnt/point')))
        ;
        $this
            ->processBuilder
            ->expects($this->once())
            ->method('setTimeout')
            ->with($this->equalTo(0.0))
        ;

        $fsMount->mounted('/mnt/point');
    }


    /**
     * @covers \Droid\Plugin\Fs\Model\FsMount::mount
     * @expectedException InvalidArgumentException
     */
    public function testMountWithInvalidArgumentWillThrowException()
    {
        $fsMount = new FsMount($this->processBuilder);

        $fsMount->mount(0);
    }

    /**
     * @covers \Droid\Plugin\Fs\Model\FsMount::__construct
     * @covers \Droid\Plugin\Fs\Model\FsMount::mount
     * @expectedException RuntimeException
     * @expectedExceptionMessage Failed to mount
     */
    public function testMountWhenProcessFailsWillThrowException()
    {
        $fsMount = new FsMount($this->processBuilder);

        $this
            ->process
            ->method('run')
            ->willReturn(1)
        ;

        $this
            ->processBuilder
            ->expects($this->once())
            ->method('setArguments')
            ->with($this->equalTo(array('mount', '/mnt/point')))
        ;
        $this
            ->processBuilder
            ->expects($this->once())
            ->method('setTimeout')
            ->with($this->equalTo(0.0))
        ;

        $fsMount->mount('/mnt/point');
    }

    /**
     * @covers \Droid\Plugin\Fs\Model\FsMount::mount
     */
    public function testMountWithUnmountedFilesystemWillReturnTrue()
    {
        $fsMount = new FsMount($this->processBuilder);

        $this
            ->process
            ->method('run')
            ->willReturn(0)
        ;

        $fsMount->mount('/mnt/point');
    }


    /**
     * @covers \Droid\Plugin\Fs\Model\FsMount::umount
     * @expectedException InvalidArgumentException
     */
    public function testUmountWithInvalidArgumentWillThrowException()
    {
        $fsMount = new FsMount($this->processBuilder);

        $fsMount->umount(0);
    }

    /**
     * @covers \Droid\Plugin\Fs\Model\FsMount::__construct
     * @covers \Droid\Plugin\Fs\Model\FsMount::umount
     * @expectedException RuntimeException
     * @expectedExceptionMessage Failed to umount
     */
    public function testUmountWhenProcessFailsWillThrowException()
    {
        $fsMount = new FsMount($this->processBuilder);

        $this
            ->process
            ->method('run')
            ->willReturn(1)
        ;

        $this
            ->processBuilder
            ->expects($this->once())
            ->method('setArguments')
            ->with($this->equalTo(array('umount', '/mnt/point')))
        ;
        $this
            ->processBuilder
            ->expects($this->once())
            ->method('setTimeout')
            ->with($this->equalTo(0.0))
        ;

        $fsMount->umount('/mnt/point');
    }

    /**
     * @covers \Droid\Plugin\Fs\Model\FsMount::umount
     */
    public function testUmountWithMountedFilesystemWillReturnTrue()
    {
        $fsMount = new FsMount($this->processBuilder);

        $this
            ->process
            ->method('run')
            ->willReturn(0)
        ;

        $fsMount->umount('/mnt/point');
    }
}
