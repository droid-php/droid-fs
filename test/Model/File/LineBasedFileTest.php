<?php

namespace Droid\Test\Plugin\Fs\Model\File;

use DomainException;
use org\bovigo\vfs\vfsStream;
use PHPUnit_Framework_TestCase;

use Droid\Plugin\Fs\Model\File\LineFactory;
use Droid\Plugin\Fs\Model\File\LineBasedFile;
use Droid\Plugin\Fs\Model\File\LineInterface;

class LineBasedFileTest extends PHPUnit_Framework_TestCase
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
     * @expectedException \Droid\Plugin\Fs\Model\File\UnusableFileException
     * @expectedExceptionMessage Expected the file to be readable
     *
     * @covers \Droid\Plugin\Fs\Model\File\LineBasedFile::__construct
     * @covers \Droid\Plugin\Fs\Model\File\LineBasedFile::setLine
     * @covers \Droid\Plugin\Fs\Model\File\LineBasedFile::open
     */
    public function testAddEntryWithUnreadableFstabFileWillThrowException()
    {
        $fstab = new LineBasedFile(
            $this->lineFac,
            vfsStream::newFile('fstab.unreadable', 0200)->at($this->vfs)->url()
        );

        $fstab->setLine($this->line);
    }

    /**
     * @expectedException \Droid\Plugin\Fs\Model\File\UnusableFileException
     * @expectedExceptionMessage Expected the file to be writeable
     *
     * @covers \Droid\Plugin\Fs\Model\File\LineBasedFile::setLine
     * @covers \Droid\Plugin\Fs\Model\File\LineBasedFile::open
     */
    public function testAddEntryWithUnwritableFstabFileWillThrowException()
    {
        $fstab = new LineBasedFile(
            $this->lineFac,
            vfsStream::newFile('fstab.unwriteable', 0400)->at($this->vfs)->url()
        );

        $fstab->setLine($this->line);
    }

    /**
     * @expectedException \Droid\Plugin\Fs\Model\File\UnusableFileException
     * @expectedExceptionMessage Expected the file to be well formed
     *
     * @covers \Droid\Plugin\Fs\Model\File\LineBasedFile::setLine
     * @covers \Droid\Plugin\Fs\Model\File\LineBasedFile::parse
     */
    public function testAddEntryWithUnparseableFstabWillThrowFstabException()
    {
        $fstab = new LineBasedFile(
            $this->lineFac,
            vfsStream::newFile('fstab')
                ->at($this->vfs)
                ->setContent('invalid-fs /dev/invalid')
                ->url()
        );

        $this
            ->line
            ->method('set')
            ->willThrowException(new DomainException)
        ;

        $fstab->setLine($this->line);
    }

    /**
     * @covers \Droid\Plugin\Fs\Model\File\LineBasedFile::backup
     * @covers \Droid\Plugin\Fs\Model\File\LineBasedFile::open
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
            ->method('getMappingValues')
            ->willReturn(array())
        ;
        $this
            ->line
            ->method('__toString')
            ->willReturn($expectedLines[0])
        ;

        $fstab = new LineBasedFile($this->lineFac, $sourceFile->url());

        $fstab
            ->setLine($this->line)
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
     * @covers \Droid\Plugin\Fs\Model\File\LineBasedFile::setLine
     * @covers \Droid\Plugin\Fs\Model\File\LineBasedFile::parse
     * @covers \Droid\Plugin\Fs\Model\File\LineBasedFile::changed
     * @covers \Droid\Plugin\Fs\Model\File\LineBasedFile::finish
     */
    public function testAddEntryWithEmptyFstabAndNewLineWillAppendNewLine()
    {
        $expectedLines = array(
            '/dev/sda1 /mnt/point ext3 rw 1 2',
            '',
        );

        $fstab = new LineBasedFile(
            $this->lineFac,
            vfsStream::newFile('fstab')->at($this->vfs)->setContent('')->url()
        );

        $this
            ->line
            ->method('getMappingValues')
            ->willReturn(array())
        ;
        $this
            ->line
            ->method('__toString')
            ->willReturn($expectedLines[0])
        ;

        $fstab
            ->setLine($this->line)
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
     * @covers \Droid\Plugin\Fs\Model\File\LineBasedFile::setLine
     * @covers \Droid\Plugin\Fs\Model\File\LineBasedFile::open
     * @covers \Droid\Plugin\Fs\Model\File\LineBasedFile::parse
     * @covers \Droid\Plugin\Fs\Model\File\LineBasedFile::changed
     * @covers \Droid\Plugin\Fs\Model\File\LineBasedFile::finish
     */
    public function testAddEntryWithEmptyFstabAndTwoNewLinesWillAppendTwoNewLines()
    {
        $expectedLines = array(
            '/dev/sda1 /mnt/point1 ext3 rw 1 2',
            '/dev/sdb1 /mnt/point2 ext4 rw,remount 0 0',
            '',
        );

        $fstab = new LineBasedFile(
            $this->lineFac,
            vfsStream::newFile('fstab')->at($this->vfs)->setContent('')->url()
        );

        $this
            ->line
            ->method('getMappingValues')
            ->willReturn(array())
        ;
        $this
            ->line
            ->method('__toString')
            ->willReturnOnConsecutiveCalls(
                $expectedLines[0],
                $expectedLines[1]
            )
        ;

        $fstab
            ->setLine($this->line)
            ->setLine($this->line)
            ->finish()
        ;

        $this->assertTrue(
            $fstab->changed(),
            'Fstab reports that the fstab file has changes to be written.'
        );

        $this->assertSame(
            implode("\n", $expectedLines),
            file_get_contents(vfsStream::url('etc/fstab')),
            'Two new entries are written to an empty fstab file.'
        );
    }

    /**
     * @covers \Droid\Plugin\Fs\Model\File\LineBasedFile::setLine
     * @covers \Droid\Plugin\Fs\Model\File\LineBasedFile::parse
     * @covers \Droid\Plugin\Fs\Model\File\LineBasedFile::changed
     * @covers \Droid\Plugin\Fs\Model\File\LineBasedFile::finish
     */
    public function testAddEntryWithNonEmptyFstabAndNewLineWillAppendNewLine()
    {
        $expectedLines = array(
            '/dev/sda1 /mnt/point1 ext3 rw 1 2',
            '/dev/sdb1 /mnt/point2 ext4 rw,remount 0 0',
            '',
        );

        $fstab = new LineBasedFile(
            $this->lineFac,
            vfsStream::newFile('fstab')
                ->at($this->vfs)
                ->setContent($expectedLines[0])
                ->url()
        );

        $this
            ->line
            ->expects($this->once())
            ->method('isData')
            ->willReturn(true)
        ;
        $this
            ->line
            ->expects($this->exactly(2))
            ->method('getMappingValues')
            ->willReturnOnConsecutiveCalls(
                array('/dev/sda1', '/mnt/point1'),
                array('/dev/sdb1', '/mnt/point2')
            )
        ;
        $this
            ->line
            ->method('__toString')
            ->willReturn($expectedLines[1])
        ;

        $fstab
            ->setLine($this->line)
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
     * @covers \Droid\Plugin\Fs\Model\File\LineBasedFile::setLine
     * @covers \Droid\Plugin\Fs\Model\File\LineBasedFile::parse
     * @covers \Droid\Plugin\Fs\Model\File\LineBasedFile::changed
     * @covers \Droid\Plugin\Fs\Model\File\LineBasedFile::finish
     */
    public function testAddEntryWithExistingLineWillDoNothing()
    {
        $expectedLines = array(
            '/dev/sda1 /mnt/point1 ext3 rw 1 2',
            '/dev/sdb1 /mnt/point2 ext4 rw,remount 0 0',
            '',
        );

        $fstab = new LineBasedFile(
            $this->lineFac,
            vfsStream::newFile('fstab')
                ->at($this->vfs)
                ->setContent(implode("\n", $expectedLines))
                ->url()
        );

        $this
            ->line
            ->expects($this->exactly(2))
            ->method('isData')
            ->willReturn(true)
        ;
        $this
            ->line
            ->expects($this->exactly(3))
            ->method('getMappingValues')
            ->willReturnOnConsecutiveCalls(
                array('/dev/sda1', '/mnt/point1'),
                array('/dev/sdb1', '/mnt/point2'),
                array('/dev/sdb1', '/mnt/point2')
            )
        ;
        $this
            ->line
            ->expects($this->once())
            ->method('changed')
            ->willReturn(false)
        ;

        $fstab
            ->setLine($this->line)
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
     * @covers \Droid\Plugin\Fs\Model\File\LineBasedFile::setLine
     * @covers \Droid\Plugin\Fs\Model\File\LineBasedFile::parse
     * @covers \Droid\Plugin\Fs\Model\File\LineBasedFile::changed
     * @covers \Droid\Plugin\Fs\Model\File\LineBasedFile::finish
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

        $fstab = new LineBasedFile(
            $this->lineFac,
            vfsStream::newFile('fstab')
                ->at($this->vfs)
                ->setContent(implode("\n", $initialLines))
                ->url()
        );

        $this
            ->line
            ->method('isData')
            ->willReturn(true)
        ;
        $this
            ->line
            ->expects($this->exactly(3))
            ->method('getMappingValues')
            ->willReturnOnConsecutiveCalls(
                array('/dev/sda1', '/mnt/point1'),
                array('/dev/sdb1', '/mnt/point2'),
                array('/dev/sdb1', '/mnt/point2')
            )
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
            ->setLine($this->line)
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
     * @covers \Droid\Plugin\Fs\Model\File\LineBasedFile::setLine
     * @covers \Droid\Plugin\Fs\Model\File\LineBasedFile::parse
     * @covers \Droid\Plugin\Fs\Model\File\LineBasedFile::changed
     * @covers \Droid\Plugin\Fs\Model\File\LineBasedFile::finish
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

        $fstab = new LineBasedFile(
            $this->lineFac,
            vfsStream::newFile('fstab')
                ->at($this->vfs)
                ->setContent(implode("\n", $initialLines))
                ->url()
        );

        $this
            ->line
            ->method('isData')
            ->willReturn(true)
        ;
        $this
            ->line
            ->expects($this->exactly(3))
            ->method('getMappingValues')
            ->willReturnOnConsecutiveCalls(
                array('/dev/sda1', '/mnt/point1'),
                array('/dev/sda1', '/mnt/point1'),
                array('/dev/sdb1', '/mnt/point2')
            )
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
            ->setLine($this->line)
            ->setLine($this->line)
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
     * @covers \Droid\Plugin\Fs\Model\File\LineBasedFile::setLine
     * @covers \Droid\Plugin\Fs\Model\File\LineBasedFile::parse
     * @covers \Droid\Plugin\Fs\Model\File\LineBasedFile::changed
     * @covers \Droid\Plugin\Fs\Model\File\LineBasedFile::finish
     */
    public function testAddEntryWithTwoCancellingUpdatesToExistingLineWillDoNothing()
    {
        $initialLines = array(
            '/dev/sda1 /mnt/point1 ext3 rw 1 2',
        );

        $expectedLines = $initialLines;

        $fstab = new LineBasedFile(
            $this->lineFac,
            vfsStream::newFile('fstab')
                ->at($this->vfs)
                ->setContent(implode("\n", $initialLines))
                ->url()
        );

        $this
            ->line
            ->method('isData')
            ->willReturn(true)
        ;
        $this
            ->line
            ->method('getFieldValue')
            ->with($this->equalTo('fileSystem'))
            ->willReturn('/dev/sda1')
        ;
        $this
            ->line
            ->method('getFieldValue')
            ->with($this->equalTo('mountPoint'))
            ->willReturn('/mnt/point1')
        ;
        $this
            ->line
            ->expects($this->exactly(3))
            ->method('getMappingValues')
            ->willReturnOnConsecutiveCalls(
                array('/dev/sda1', '/mnt/point1'),
                array('/dev/sda1', '/mnt/point1'),
                array('/dev/sda1', '/mnt/point1')
            )
        ;
        $this
            ->line
            ->method('changed')
            ->willReturnOnConsecutiveCalls(true, false)
        ;

        $fstab
            ->setLine($this->line)
            ->setLine($this->line)
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
            ->getMockBuilder(LineInterface::class)
            ->getMock()
        ;
        return $mock;
    }
}
