<?php

namespace Droid\Test\Plugin\Model\Fstab;

use org\bovigo\vfs\vfsStream;
use PHPUnit_Framework_TestCase;

use Droid\Plugin\Fs\Model\Fstab\Fstab;
use Droid\Plugin\Fs\Model\Fstab\FstabException;
use Droid\Plugin\Fs\Model\Fstab\FstabLine;
use Droid\Plugin\Fs\Model\Fstab\FstabLineFactory;

class FstabTest extends PHPUnit_Framework_TestCase
{
    protected $vfs;
    protected $line;
    protected $lineFac;

    protected function setUp()
    {
        $this->vfs = vfsStream::setup('etc');
        $this->line = $this->getLineMock();
        $this->lineFac = $this->getLineFacMock($this->line);
    }

    /**
     * @expectedException \Droid\Plugin\Fs\Model\Fstab\FstabException
     * @expectedExceptionMessage Expected the fstab file to be a readable file
     *
     * @covers \Droid\Plugin\Fs\Model\Fstab\Fstab::__construct
     * @covers \Droid\Plugin\Fs\Model\Fstab\Fstab::addEntry
     * @covers \Droid\Plugin\Fs\Model\Fstab\Fstab::open
     */
    public function testAddEntryWithUnreadableFstabFileWillThrowException()
    {
        $fstab = new Fstab(
            $this->lineFac,
            vfsStream::newFile('fstab.unreadable', 0200)->at($this->vfs)->url()
        );

        $fstab->addEntry('/dev/sda1', '/mnt/point', 'ext3', 'rw', 1, 2);
    }

    /**
     * @expectedException \Droid\Plugin\Fs\Model\Fstab\FstabException
     * @expectedExceptionMessage Expected the fstab file to be writeable
     *
     *@covers \Droid\Plugin\Fs\Model\Fstab\Fstab::addEntry
     * @covers \Droid\Plugin\Fs\Model\Fstab\Fstab::open
     */
    public function testAddEntryWithUnwritableFstabFileWillThrowException()
    {
        $fstab = new Fstab(
            $this->lineFac,
            vfsStream::newFile('fstab.unwriteable', 0400)->at($this->vfs)->url()
        );

        $fstab->addEntry('/dev/sda1', '/mnt/point', 'ext3', 'rw', 1, 2);
    }

    /**
     * @expectedException \Droid\Plugin\Fs\Model\Fstab\FstabException
     * @expectedExceptionMessage Expected the fstab file to be well formed
     *
     * @covers \Droid\Plugin\Fs\Model\Fstab\Fstab::addEntry
     * @covers \Droid\Plugin\Fs\Model\Fstab\Fstab::parse
     */
    public function testAddEntryWithUnparseableFstabWillThrowFstabException()
    {
        $fstab = new Fstab(
            $this->lineFac,
            vfsStream::newFile('fstab')
                ->at($this->vfs)
                ->setContent('invalid-fs /dev/invalid')
                ->url()
        );

        $this
            ->line
            ->method('setContent')
            ->willThrowException(new FstabException)
        ;

        $fstab->addEntry('/dev/sda1', '/mnt/point', 'ext3', 'rw', 1, 2);
    }

    /**
     * @covers \Droid\Plugin\Fs\Model\Fstab\Fstab::backup
     * @covers \Droid\Plugin\Fs\Model\Fstab\Fstab::open
     */
    public function testBackupWithChangedFstabWillWriteOriginalFstabToBackup()
    {
        $expectedLines = array(
            '/dev/sda1 /mnt/point1 ext3 rw 1 2',
        );
        $sourceFile = vfsStream::newFile('fstab')
            ->at($this->vfs)
            ->setContent($expectedLines[0])
        ;
        $backupPath = vfsStream::url(
            sprintf('etc/fstab.%s.backup', date('Y-m-d_H-i-s'))
        );

        $this
            ->line
            ->method('__toString')
            ->willReturn($expectedLines[0])
        ;

        $fstab = new Fstab($this->lineFac, $sourceFile->url());

        $fstab
            ->addEntry('/dev/sdb1', '/mnt/point2', 'ext4', 'rw,remount')
            ->backup($backupPath);
        ;

        $this->assertTrue(
            $fstab->changed(),
            'Fstab reports that the fstab file has changes to be written.'
        );

        $this->assertTrue(
            file_exists($backupPath),
            'Fstab has created the backup file.'
        );

        $this->assertSame(
            implode("\n", $expectedLines),
            file_get_contents($backupPath),
            'The backup file contains the original fstab content.'
        );
    }

    /**
     * @covers \Droid\Plugin\Fs\Model\Fstab\Fstab::addEntry
     * @covers \Droid\Plugin\Fs\Model\Fstab\Fstab::parse
     * @covers \Droid\Plugin\Fs\Model\Fstab\Fstab::changed
     * @covers \Droid\Plugin\Fs\Model\Fstab\Fstab::finish
     */
    public function testAddEntryWithEmptyFstabAndNewLineWillAppendNewLine()
    {
        $expectedLines = array(
            '/dev/sda1 /mnt/point ext3 rw 1 2',
            '',
        );

        $fstab = new Fstab(
            $this->lineFac,
            vfsStream::newFile('fstab')->at($this->vfs)->setContent('')->url()
        );

        $this
            ->line
            ->method('__toString')
            ->willReturn($expectedLines[0])
        ;

        $fstab
            ->addEntry('/dev/sda1', '/mnt/point', 'ext3', 'rw', 1, 2)
            ->finish()
        ;

        $this->assertTrue(
            $fstab->changed(),
            'Fstab reports that the fstab file has changes to be written.'
        );

        $this->assertSame(
            implode("\n", $expectedLines),
            file_get_contents(vfsStream::url('etc/fstab')),
            'A new entry is written to an empty fstab file.'
        );
    }

    /**
     * @covers \Droid\Plugin\Fs\Model\Fstab\Fstab::addEntry
     * @covers \Droid\Plugin\Fs\Model\Fstab\Fstab::open
     * @covers \Droid\Plugin\Fs\Model\Fstab\Fstab::parse
     * @covers \Droid\Plugin\Fs\Model\Fstab\Fstab::changed
     * @covers \Droid\Plugin\Fs\Model\Fstab\Fstab::finish
     */
    public function testAddEntryWithEmptyFstabAndTwoNewLinesWillAppendTwoNewLines()
    {
        $expectedLines = array(
            '/dev/sda1 /mnt/point1 ext3 rw 1 2',
            '/dev/sdb1 /mnt/point2 ext4 rw,remount 0 0',
            '',
        );

        $fstab = new Fstab(
            $this->lineFac,
            vfsStream::newFile('fstab')->at($this->vfs)->setContent('')->url()
        );

        $this
            ->line
            ->method('__toString')
            ->willReturnOnConsecutiveCalls(
                $expectedLines[0],
                $expectedLines[1]
            )
        ;

        $fstab
            ->addEntry('/dev/sda1', '/mnt/point1', 'ext3', 'rw', 1, 2)
            ->addEntry('/dev/sdb1', '/mnt/point2', 'ext4', 'rw,remount')
            ->finish()
        ;

        $this->assertTrue(
            $fstab->changed(),
            'Fstab reports that the fstab file has changes to be written.'
        );

        $this->assertSame(
            implode("\n", $expectedLines),
            file_get_contents(vfsStream::url('etc/fstab')),
            'Two new entries is written to an empty fstab file.'
        );
    }

    /**
     * @covers \Droid\Plugin\Fs\Model\Fstab\Fstab::addEntry
     * @covers \Droid\Plugin\Fs\Model\Fstab\Fstab::parse
     * @covers \Droid\Plugin\Fs\Model\Fstab\Fstab::changed
     * @covers \Droid\Plugin\Fs\Model\Fstab\Fstab::finish
     */
    public function testAddEntryWithNonEmptyFstabAndNewLineWillAppendNewLine()
    {
        $expectedLines = array(
            '/dev/sda1 /mnt/point1 ext3 rw 1 2',
            '/dev/sdb1 /mnt/point2 ext4 rw,remount 0 0',
            '',
        );

        $fstab = new Fstab(
            $this->lineFac,
            vfsStream::newFile('fstab')
                ->at($this->vfs)
                ->setContent($expectedLines[0])
                ->url()
        );

        $this
            ->line
            ->expects($this->once())
            ->method('isParsedFileSystemInfo')
            ->willReturn(true)
        ;
        $this
            ->line
            ->expects($this->once())
            ->method('getFileSystem')
            ->willReturn(true)
        ;
        $this
            ->line
            ->expects($this->once())
            ->method('getMountPoint')
            ->willReturn(true)
        ;
        $this
            ->line
            ->method('__toString')
            ->willReturn($expectedLines[1])
        ;

        $fstab
            ->addEntry('/dev/sdb1', '/mnt/point2', 'ext4', 'rw,remount')
            ->finish()
        ;

        $this->assertTrue(
            $fstab->changed(),
            'Fstab reports that the fstab file has changes to be written.'
        );

        $this->assertSame(
            implode("\n", $expectedLines),
            file_get_contents(vfsStream::url('etc/fstab')),
            'A new entry is appended to a non-empty fstab file.'
        );
    }

    /**
     * @covers \Droid\Plugin\Fs\Model\Fstab\Fstab::addEntry
     * @covers \Droid\Plugin\Fs\Model\Fstab\Fstab::parse
     * @covers \Droid\Plugin\Fs\Model\Fstab\Fstab::changed
     * @covers \Droid\Plugin\Fs\Model\Fstab\Fstab::finish
     */
    public function testAddEntryWithExistingLineWillDoNothing()
    {
        $expectedLines = array(
            '/dev/sda1 /mnt/point1 ext3 rw 1 2',
            '/dev/sdb1 /mnt/point2 ext4 rw,remount 0 0',
            '',
        );

        $fstab = new Fstab(
            $this->lineFac,
            vfsStream::newFile('fstab')
                ->at($this->vfs)
                ->setContent(implode("\n", $expectedLines))
                ->url()
        );

        $this
            ->line
            ->expects($this->exactly(2))
            ->method('isParsedFileSystemInfo')
            ->willReturn(true)
        ;
        $this
            ->line
            ->expects($this->exactly(2))
            ->method('getFileSystem')
            ->willReturnOnConsecutiveCalls('/dev/sda1', '/dev/sdb1')
        ;
        $this
            ->line
            ->expects($this->exactly(2))
            ->method('getMountPoint')
            ->willReturnOnConsecutiveCalls('/mnt/point1', '/mnt/point2')
        ;
        $this
            ->line
            ->expects($this->once())
            ->method('changed')
            ->willReturn(false)
        ;

        $fstab
            ->addEntry('/dev/sdb1', '/mnt/point2', 'ext4', 'rw,remount')
            ->finish()
        ;

        $this->assertFalse(
            $fstab->changed(),
            'Fstab reports that the fstab file does not have changes to be written.'
        );

        $this->assertSame(
            implode("\n", $expectedLines),
            file_get_contents(vfsStream::url('etc/fstab')),
            'Zero entries are added to a non-empty fstab file.'
        );
    }

    /**
     * @covers \Droid\Plugin\Fs\Model\Fstab\Fstab::addEntry
     * @covers \Droid\Plugin\Fs\Model\Fstab\Fstab::parse
     * @covers \Droid\Plugin\Fs\Model\Fstab\Fstab::changed
     * @covers \Droid\Plugin\Fs\Model\Fstab\Fstab::finish
     */
    public function testAddEntryWithUpdateToExistingLineWillUpdateLine()
    {
        $initialLines = array(
            '/dev/sda1 /mnt/point1 ext3 rw 1 2',
            '/dev/sdb1 /mnt/point2 ext4 rw,remount 0 0',
        );
        $expectedLines = array(
            '/dev/sda1 /mnt/point1 ext3 rw 1 2',
            '/dev/sdb1 /mnt/point2 ext3 rw,remount 0 0',
        );

        $fstab = new Fstab(
            $this->lineFac,
            vfsStream::newFile('fstab')
                ->at($this->vfs)
                ->setContent(implode("\n", $initialLines))
                ->url()
        );

        $this
            ->line
            ->method('isParsedFileSystemInfo')
            ->willReturn(true)
        ;
        $this
            ->line
            ->method('getFileSystem')
            ->willReturnOnConsecutiveCalls('/dev/sda1', '/dev/sdb1')
        ;
        $this
            ->line
            ->method('getMountPoint')
            ->willReturnOnConsecutiveCalls('/mnt/point1', '/mnt/point2')
        ;
        $this
            ->line
            ->method('changed')
            ->willReturn(true)
        ;
        $this
            ->line
            ->method('__toString')
            ->willReturn($expectedLines[1])
        ;

        $fstab
            ->addEntry('/dev/sdb1', '/mnt/point2', 'ext3', 'rw,remount')
            ->finish()
        ;

        $this->assertTrue(
            $fstab->changed(),
            'Fstab reports that the fstab file has changes to be written.'
        );

        $this->assertSame(
            implode("\n", $expectedLines),
            file_get_contents(vfsStream::url('etc/fstab')),
            'An entry in the fstab file is updated.'
        );
    }

    /**
     * @covers \Droid\Plugin\Fs\Model\Fstab\Fstab::addEntry
     * @covers \Droid\Plugin\Fs\Model\Fstab\Fstab::parse
     * @covers \Droid\Plugin\Fs\Model\Fstab\Fstab::changed
     * @covers \Droid\Plugin\Fs\Model\Fstab\Fstab::finish
     */
    public function testAddEntryWillUpdateLineAndAppendLine()
    {
        $initialLines = array(
            '/dev/sda1 /mnt/point1 ext3 rw 1 2',
        );
        $expectedLines = array(
            '/dev/sda1 /mnt/point1 ext3 ro 1 2',
            '/dev/sdb1 /mnt/point2 ext4 rw,remount 0 0',
            '',
        );

        $fstab = new Fstab(
            $this->lineFac,
            vfsStream::newFile('fstab')
                ->at($this->vfs)
                ->setContent(implode("\n", $initialLines))
                ->url()
        );

        $this
            ->line
            ->method('isParsedFileSystemInfo')
            ->willReturn(true)
        ;
        $this
            ->line
            ->method('getFileSystem')
            ->willReturn('/dev/sda1')
        ;
        $this
            ->line
            ->method('getMountPoint')
            ->willReturn('/mnt/point1')
        ;
        $this
            ->line
            ->method('changed')
            ->willReturn(true)
        ;
        $this
            ->line
            ->method('__toString')
            ->willReturnOnConsecutiveCalls($expectedLines[0], $expectedLines[1])
        ;

        $fstab
            ->addEntry('/dev/sda1', '/mnt/point1', 'ext3', 'ro', 1, 2)
            ->addEntry('/dev/sdb1', '/mnt/point2', 'ext4', 'rw,remount')
            ->finish()
        ;

        $this->assertTrue(
            $fstab->changed(),
            'Fstab reports that the fstab file has changes to be written.'
        );

        $this->assertSame(
            implode("\n", $expectedLines),
            file_get_contents(vfsStream::url('etc/fstab')),
            'An entry in the fstab file is updated and a new entry appended.'
        );
    }

    /**
     * @covers \Droid\Plugin\Fs\Model\Fstab\Fstab::addEntry
     * @covers \Droid\Plugin\Fs\Model\Fstab\Fstab::parse
     * @covers \Droid\Plugin\Fs\Model\Fstab\Fstab::changed
     * @covers \Droid\Plugin\Fs\Model\Fstab\Fstab::finish
     */
    public function testAddEntryWithTwoCancellingUpdatesToExistingLineWillDoNothing()
    {
        $initialLines = array(
            '/dev/sda1 /mnt/point1 ext3 rw 1 2',
        );

        $expectedLines = $initialLines;

        $fstab = new Fstab(
            $this->lineFac,
            vfsStream::newFile('fstab')
                ->at($this->vfs)
                ->setContent(implode("\n", $initialLines))
                ->url()
        );

        $this
            ->line
            ->method('isParsedFileSystemInfo')
            ->willReturn(true)
        ;
        $this
            ->line
            ->method('getFileSystem')
            ->willReturn('/dev/sda1')
        ;
        $this
            ->line
            ->method('getMountPoint')
            ->willReturn('/mnt/point1')
        ;
        $this
            ->line
            ->method('changed')
            ->willReturnOnConsecutiveCalls(true, false)
        ;

        $fstab
            ->addEntry('/dev/sda1', '/mnt/point1', 'ext3', 'ro', 1, 2)
            ->addEntry('/dev/sda1', '/mnt/point1', 'ext3', 'rw', 1, 2)
            ->finish()
        ;

        $this->assertFalse(
            $fstab->changed(),
            'Fstab reports that the fstab file does not have changes to be written.'
        );

        $this->assertSame(
            implode("\n", $expectedLines),
            file_get_contents(vfsStream::url('etc/fstab')),
            'An entry in the fstab file is unchanged.'
        );
    }

    private function getLineFacMock($lineToMake)
    {
        $mock = $this
            ->getMockBuilder(FstabLineFactory::class)
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
