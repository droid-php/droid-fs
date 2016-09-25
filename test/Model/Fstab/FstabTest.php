<?php

namespace Droid\Test\Plugin\Fs\Model\Fstab;

use org\bovigo\vfs\vfsStream;
use PHPUnit_Framework_TestCase;

use Droid\Lib\Plugin\Model\File\LineFactory;
use Droid\Lib\Plugin\Model\File\UnusableFileException;
use Droid\Plugin\Fs\Model\Fstab\Fstab;
use Droid\Plugin\Fs\Model\Fstab\FstabLine;

class FstabTest extends PHPUnit_Framework_TestCase
{
    protected $fstab;
    protected $line;
    protected $lineFac;
    protected $vfs;

    protected function setUp()
    {
        $this->vfs = vfsStream::setup('etc');
        $this->line = $this->getLineMock();
        $this->lineFac = $this->getLineFacMock($this->line);
        $this->fstab = $this->getFstabMock(
            $this->lineFac,
            vfsStream::newFile('fstab')->at($this->vfs)->url()
        );
    }

    /**
     * @covers \Droid\Plugin\Fs\Model\Fstab\Fstab::addEntry
     */
    public function testAddEntryWillInvokeLineFactoryToMakeALine()
    {
        $this
            ->lineFac
            ->expects($this->once())
            ->method('makeLine')
        ;

        $this->fstab->addEntry('/dev/sda1', '/mnt/point', 'ext3');
    }

    /**
     * @covers \Droid\Plugin\Fs\Model\Fstab\Fstab::addEntry
     */
    public function testAddEntryWillMutateLineWithGivenArgs()
    {
        $this
            ->line
            ->expects($this->once())
            ->method('setFileSystem')
            ->with($this->equalTo('/dev/sda1'))
        ;
        $this
            ->line
            ->expects($this->once())
            ->method('setMountPoint')
            ->with($this->equalTo('/mnt/point'))
        ;
        $this
            ->line
            ->expects($this->once())
            ->method('setFileSystemType')
            ->with($this->equalTo('ext3'))
        ;
        $this
            ->line
            ->expects($this->once())
            ->method('setOptions')
            ->with($this->equalTo('rw'))
        ;
        $this
            ->line
            ->expects($this->once())
            ->method('setDump')
            ->with($this->equalTo(1))
        ;
        $this
            ->line
            ->expects($this->once())
            ->method('setPass')
            ->with($this->equalTo(2))
        ;

        $this->fstab->addEntry('/dev/sda1', '/mnt/point', 'ext3', 'rw', 1, 2);
    }

    /**
     * @covers \Droid\Plugin\Fs\Model\Fstab\Fstab::addEntry
     */
    public function testAddEntryWillMutateLineWithDefaultArgs()
    {
        $this
            ->line
            ->expects($this->once())
            ->method('setOptions')
            ->with($this->equalTo('ro'))
        ;
        $this
            ->line
            ->expects($this->once())
            ->method('setDump')
            ->with($this->equalTo(0))
        ;
        $this
            ->line
            ->expects($this->once())
            ->method('setPass')
            ->with($this->equalTo(0))
        ;

        $this->fstab->addEntry('/dev/sda1', '/mnt/point', 'ext3');
    }

    /**
     * @covers \Droid\Plugin\Fs\Model\Fstab\Fstab::addEntry
     */
    public function testAddEntryWillInvokeSetLine()
    {
        $this
            ->fstab
            ->expects($this->once())
            ->method('setLine')
            ->with($this->equalTo($this->line))
        ;

        $this->fstab->addEntry('/dev/sda1', '/mnt/point', 'ext3');
    }

    /**
     * @expectedException \Droid\Plugin\Fs\Model\Fstab\FstabException
     * @expectedExceptionMessage Unable to add an entry to the fstab file because it is unusable
     *
     * @covers \Droid\Plugin\Fs\Model\Fstab\Fstab::addEntry
     */
    public function testAddEntryWithUnusableFileFileWillThrowException()
    {
        $this
            ->fstab
            ->method('setLine')
            ->willThrowException(new UnusableFileException)
        ;

        $this->fstab->addEntry('/dev/sda1', '/mnt/point', 'ext3');
    }

    private function getFstabMock($lineFac, $path)
    {
        $mock = $this
            ->getMockBuilder(Fstab::class)
            ->setConstructorArgs(array($lineFac, $path))
            ->setMethods(array('setLine'))
            ->getMock()
        ;
        return $mock;
    }

    private function getLineFacMock($lineToMake)
    {
        $mock = $this
            ->getMockBuilder(LineFactory::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;
        $mock
            ->method('makeLine')
            ->willReturn($lineToMake)
        ;
        return $mock;
    }

    private function getLineMock()
    {
        $mock = $this
            ->getMockBuilder(FstabLine::class)
            ->getMock()
        ;
        $mock
            ->method('setFileSystem')
            ->willReturnSelf()
        ;
        $mock
            ->method('setMountPoint')
            ->willReturnSelf()
        ;
        $mock
            ->method('setFileSystemType')
            ->willReturnSelf()
        ;
        $mock
            ->method('setOptions')
            ->willReturnSelf()
        ;
        $mock
            ->method('setDump')
            ->willReturnSelf()
        ;
        $mock
            ->method('setPass')
            ->willReturnSelf()
        ;
        return $mock;
    }
}
