<?php

namespace Droid\Test\Plugin\Fs\Command;

use RuntimeException;

use org\bovigo\vfs\vfsStream;
use PHPUnit_Framework_TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

use Droid\Plugin\Fs\Command\FsSetlineCommand;
use Droid\Lib\Plugin\Model\File\FileFactory;
use Droid\Lib\Plugin\Model\File\LineBasedFileInterface;
use Droid\Lib\Plugin\Model\File\LineFactory;
use Droid\Lib\Plugin\Model\File\LineInterface;
use Droid\Lib\Plugin\Model\File\UnusableFileException;

class FsSetlineCommandTest extends PHPUnit_Framework_TestCase
{
    protected $app;
    protected $fileFac;
    protected $lineFac;
    protected $tester;
    protected $vfs;

    protected function setUp()
    {
        $this->lineFac = $this
            ->getMockBuilder(LineFactory::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;
        $this->line = $this
            ->getMockBuilder(LineInterface::class)
            ->getMock()
        ;
        $this->fileFac = $this
            ->getMockBuilder(FileFactory::class)
            ->setConstructorArgs(array('some-classname', $this->lineFac))
            ->getMock()
        ;
        $this->file = $this
            ->getMockBuilder(LineBasedFileInterface::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;
        $this
            ->fileFac
            ->method('getLineFactory')
            ->willReturn($this->lineFac)
        ;
        $this
            ->fileFac
            ->method('makeFile')
            ->willReturn($this->file)
        ;
        $this
            ->lineFac
            ->method('makeLine')
            ->willReturn($this->line)
        ;
        $this
            ->line
            ->method('setFieldValue')
            ->willReturnSelf()
        ;

        $command = new FsSetlineCommand($this->fileFac);

        $this->app = new Application;
        $this->app->add($command);

        $this->tester = new CommandTester($command);

        $this->vfs = vfsStream::setup('etc');
    }

    protected function tearDown()
    {
        clearstatcache();
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessageRegex /The file "[^"]*" does not exist/
     * @covers \Droid\Plugin\Fs\Command\FsSetlineCommand::configure
     * @covers \Droid\Plugin\Fs\Command\FsSetlineCommand::execute
     */
    public function testCommandWithMissingFileWillThrowException()
    {
        $this->tester->execute(array(
            'command' => $this->app->find('fs:setline')->getName(),
            'file' => vfsStream::url('etc/not_a_file'),
            'option-name' => 'some-name',
            'option-value' => 'some-value',
        ));
    }

    /**
     * @covers \Droid\Plugin\Fs\Command\FsSetlineCommand::__construct
     * @covers \Droid\Plugin\Fs\Command\FsSetlineCommand::execute
     */
    public function testCommandWithSeparatorArgWillPrepareLineFactory()
    {
        $targetFile = vfsStream::newFile('some-file')->at($this->vfs);

        $this
            ->lineFac
            ->expects($this->once())
            ->method('setFieldSeparator')
            ->with($this->equalTo(': '))
        ;

        $this->tester->execute(array(
            'command' => $this->app->find('fs:setline')->getName(),
            'file' => $targetFile->url(),
            'option-name' => 'some-name',
            'option-value' => 'some-value',
            '--separator' => ': '
        ));
    }

    /**
     * @covers \Droid\Plugin\Fs\Command\FsSetlineCommand::execute
     */
    public function testCommandWillPopulateLineWithArgs()
    {
        $targetFile = vfsStream::newFile('some-file')->at($this->vfs);

        $this
            ->line
            ->expects($this->exactly(2))
            ->method('setFieldValue')
            ->withConsecutive(
                array($this->isType('string'), $this->equalTo('some-name')),
                array($this->isType('string'), $this->equalTo('some-value'))
            )
        ;

        $this->tester->execute(array(
            'command' => $this->app->find('fs:setline')->getName(),
            'file' => $targetFile->url(),
            'option-name' => 'some-name',
            'option-value' => 'some-value',
        ));
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage I cannot set a line in the file
     * @covers \Droid\Plugin\Fs\Command\FsSetlineCommand::execute
     */
    public function testCommandWithUnusableFileWillThrowException()
    {
        $targetFile = vfsStream::newFile('some-file')->at($this->vfs);

        $this
            ->file
            ->expects($this->once())
            ->method('setLine')
            ->with($this->equalTo($this->line))
            ->willThrowException(new UnusableFileException)
        ;

        $this->tester->execute(array(
            'command' => $this->app->find('fs:setline')->getName(),
            'file' => $targetFile->url(),
            'option-name' => 'some-name',
            'option-value' => 'some-value',
        ));
    }

    /**
     * @covers \Droid\Plugin\Fs\Command\FsSetlineCommand::execute
     */
    public function testCommandWithUnchangedFileWillNotMakeChanges()
    {
        $targetFile = vfsStream::newFile('some-file')->at($this->vfs);

        $this
            ->file
            ->expects($this->atLeastOnce())
            ->method('changed')
            ->willReturn(false)
        ;

        $this->tester->execute(array(
            'command' => $this->app->find('fs:setline')->getName(),
            'file' => $targetFile->url(),
            'option-name' => 'some-name',
            'option-value' => 'some-value',
        ));

        $this->assertRegExp(
            '/I am not making any changes to the file/',
            $this->tester->getDisplay()
        );

        $this->assertRegExp(
            '/"changed":\s?false/',
            $this->tester->getDisplay()
        );
    }

    /**
     * @group wip
     * @covers \Droid\Plugin\Fs\Command\FsSetlineCommand::execute
     */
    public function testCommandWithChangedFileInCheckModeWillNotMakeChanges()
    {
        $targetFile = vfsStream::newFile('some-file')->at($this->vfs);

        $this
            ->file
            ->expects($this->atLeastOnce())
            ->method('changed')
            ->willReturn(true)
        ;
        $this
            ->file
            ->expects($this->never())
            ->method('backup')
            ->willReturnSelf()
        ;

        $this->tester->execute(array(
            'command' => $this->app->find('fs:setline')->getName(),
            'file' => $targetFile->url(),
            'option-name' => 'some-name',
            'option-value' => 'some-value',
            '--check' => true,
        ));

        $this->assertRegExp(
            '/I would make a change to the file/',
            $this->tester->getDisplay()
        );

        $this->assertRegExp(
            '/"changed":\s?true/',
            $this->tester->getDisplay()
        );
    }

    /**
     * @covers \Droid\Plugin\Fs\Command\FsSetlineCommand::backupName
     * @covers \Droid\Plugin\Fs\Command\FsSetlineCommand::execute
     */
    public function testCommandWithChangedFileWillMakeChanges()
    {
        $targetFile = vfsStream::newFile('some-file')->at($this->vfs);

        $this
            ->file
            ->expects($this->atLeastOnce())
            ->method('changed')
            ->willReturn(true)
        ;
        $this
            ->file
            ->expects($this->once())
            ->method('backup')
            ->willReturnSelf()
        ;
        $this
            ->file
            ->expects($this->once())
            ->method('finish')
        ;

        $this->tester->execute(array(
            'command' => $this->app->find('fs:setline')->getName(),
            'file' => $targetFile->url(),
            'option-name' => 'some-name',
            'option-value' => 'some-value',
        ));

        $this->assertRegExp(
            '/I am making your changes to the file/',
            $this->tester->getDisplay()
        );

        $this->assertRegExp(
            '/"changed":\s?true/',
            $this->tester->getDisplay()
        );
    }
}
