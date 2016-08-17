<?php

namespace Droid\Test\Plugin\Fs\Command;

use org\bovigo\vfs\vfsStream;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

use Droid\Plugin\Fs\Command\FsTouchCommand;

class FsTouchCommandTest extends \PHPUnit_Framework_TestCase
{
    protected $app;
    protected $tester;
    protected $vfs;

    protected function setUp()
    {
        $command = new FsTouchCommand;

        $this->app = new Application;
        $this->app->add($command);

        $this->tester = new CommandTester($command);

        $this->vfs = vfsStream::setup();
    }

    public function testTouchWillExitIfNoTimestampsSuppliedAndFileExists()
    {
        vfsStream::newFile('some_file')->at($this->vfs)->setContent('hello!');

        $this->tester->execute(array(
            'command' => $this->app->find('fs:touch')->getName(),
            'file' => vfsStream::url('root/some_file'),
        ));

        $this->assertRegExp(
            '/^The file "[^"]*" exists and I was not instructed to change a timestamp/',
            $this->tester->getDisplay()
        );
    }

    public function testTouchWillExitIfNoCreateAndFileNotExists()
    {
        $this->tester->execute(array(
            'command' => $this->app->find('fs:touch')->getName(),
            'file' => vfsStream::url('root/some_file'),
            '--no-create' => true,
        ));

        $this->assertRegExp(
            '/^The file "[^"]*" does not exist and I was instructed not to create it/',
            $this->tester->getDisplay()
        );
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptonMessage I do not like the look of your atime
     */
    public function testTouchWithBogusAtimeThrowsRuntimeException()
    {
        $this->tester->execute(array(
            'command' => $this->app->find('fs:touch')->getName(),
            'file' => vfsStream::url('root/some_file'),
            '--atime' => '2099-01-99',
        ));
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptonMessage I do not like the look of your mtime
     */
    public function testTouchWithBogusMtimeThrowsRuntimeException()
    {
        $this->tester->execute(array(
            'command' => $this->app->find('fs:touch')->getName(),
            'file' => vfsStream::url('root/some_file'),
            '--mtime' => '2099-01-99',
        ));
    }

    public function testTouchDoesNothingWhenSuppliedTimestampsMatchExisting()
    {
        $atime = new \DateTime('Today');
        $mtime = new \DateTime('Yesterday');

        vfsStream::newFile('some_file')
            ->at($this->vfs)
            ->setContent('hello!')
            ->lastAccessed($atime->getTimestamp())
            ->lastModified($mtime->getTimestamp())
        ;

        $this->tester->execute(array(
            'command' => $this->app->find('fs:touch')->getName(),
            'file' => vfsStream::url('root/some_file'),
            '--atime' => $atime->format('c'),
            '--mtime' => $mtime->format('c'),
        ));

        $this->assertRegExp(
            '/^The file "[^"]*" already has the supplied timestamps. Nothing to do/',
            $this->tester->getDisplay()
        );
    }

    public function testTouchUpdatesBothTimestampsWhenBothAreSupplied()
    {
        $original_atime = new \DateTime('Today');
        $original_mtime = new \DateTime('Yesterday');

        $expected_atime = clone $original_atime;
        $expected_atime->modify('+1 hour');

        $expected_mtime = clone $original_mtime;
        $expected_mtime->modify('+1 hour');

        vfsStream::newFile('some_file')
            ->at($this->vfs)
            ->setContent('hello!')
            ->lastAccessed($original_atime->getTimestamp())
            ->lastModified($original_mtime->getTimestamp())
        ;

        $stat = stat(vfsStream::url('root/some_file'));
        $this->assertSame(
            $original_atime->getTimestamp(),
            $stat['atime'],
            'The file starts with a known atime'
        );
        $this->assertSame(
            $original_mtime->getTimestamp(),
            $stat['mtime'],
            'The file starts with a known mtime'
        );

        $this->tester->execute(array(
            'command' => $this->app->find('fs:touch')->getName(),
            'file' => vfsStream::url('root/some_file'),
            '--atime' => $expected_atime->format('c'),
            '--mtime' => $expected_mtime->format('c'),
        ));

        clearstatcache(true, vfsStream::url('root/some_file'));
        $stat = stat(vfsStream::url('root/some_file'));
        $this->assertSame(
            $expected_atime->getTimestamp(),
            $stat['atime'],
            'The touch command updates the atime of the file to the expected value'
        );
        $this->assertSame(
            $expected_mtime->getTimestamp(),
            $stat['mtime'],
            'The touch command updates the mtime of the file to the expected value'
        );

        $this->assertRegExp(
            '/^Touched file "[^"]*"/',
            $this->tester->getDisplay()
        );
    }

    public function testTouchUpdatesOnlyAtimeWhenOnlyAtimeIsSupplied()
    {
        $original_atime = new \DateTime('Today');
        $original_mtime = new \DateTime('Yesterday');

        $expected_atime = clone $original_atime;
        $expected_atime->modify('+1 hour');

        $expected_mtime = $original_mtime;

        vfsStream::newFile('some_file')
            ->at($this->vfs)
            ->setContent('hello!')
            ->lastAccessed($original_atime->getTimestamp())
            ->lastModified($original_mtime->getTimestamp())
        ;

        $stat = stat(vfsStream::url('root/some_file'));
        $this->assertSame(
            $original_atime->getTimestamp(),
            $stat['atime'],
            'The file starts with a known atime'
        );
        $this->assertSame(
            $original_mtime->getTimestamp(),
            $stat['mtime'],
            'The file starts with a known mtime'
        );

        $this->tester->execute(array(
            'command' => $this->app->find('fs:touch')->getName(),
            'file' => vfsStream::url('root/some_file'),
            '--atime' => $expected_atime->format('c'),
        ));

        clearstatcache(true, vfsStream::url('root/some_file'));
        $stat = stat(vfsStream::url('root/some_file'));
        $this->assertSame(
            $expected_atime->getTimestamp(),
            $stat['atime'],
            'The touch command updates the atime of the file to the expected value'
        );
        $this->assertSame(
            $expected_mtime->getTimestamp(),
            $stat['mtime'],
            'The touch command does not change the mtime'
        );

        $this->assertRegExp(
            '/^Touched file "[^"]*"/',
            $this->tester->getDisplay()
        );
    }

    public function testTouchUpdatesOnlyMtimeWhenOnlyMtimeIsSupplied()
    {
        $original_atime = new \DateTime('Today');
        $original_mtime = new \DateTime('Yesterday');

        $expected_atime = $original_atime;

        $expected_mtime = clone $original_mtime;
        $expected_mtime->modify('+1 hour');

        vfsStream::newFile('some_file')
            ->at($this->vfs)
            ->setContent('hello!')
            ->lastAccessed($original_atime->getTimestamp())
            ->lastModified($original_mtime->getTimestamp())
        ;

        $stat = stat(vfsStream::url('root/some_file'));
        $this->assertSame(
            $original_atime->getTimestamp(),
            $stat['atime'],
            'The file starts with a known atime'
        );
        $this->assertSame(
            $original_mtime->getTimestamp(),
            $stat['mtime'],
            'The file starts with a known mtime'
        );

        $this->tester->execute(array(
            'command' => $this->app->find('fs:touch')->getName(),
            'file' => vfsStream::url('root/some_file'),
            '--mtime' => $expected_mtime->format('c'),
        ));

        clearstatcache(true, vfsStream::url('root/some_file'));
        $stat = stat(vfsStream::url('root/some_file'));
        $this->assertSame(
            $expected_atime->getTimestamp(),
            $stat['atime'],
            'The touch command does not change the atime'
        );
        $this->assertSame(
            $expected_mtime->getTimestamp(),
            $stat['mtime'],
            'The touch command updates the mtime of the file to the expected value'
        );

        $this->assertRegExp(
            '/^Touched file "[^"]*"/',
            $this->tester->getDisplay()
        );
    }

    public function testTouchCreatesNonExistingFile()
    {
        $this->assertFalse($this->vfs->hasChild('some_file'));

        $this->tester->execute(array(
            'command' => $this->app->find('fs:touch')->getName(),
            'file' => vfsStream::url('root/some_file')
        ));

        $this->assertTrue($this->vfs->hasChild('some_file'));
        $this->assertRegExp(
            '/^Touched file "[^"]*"/',
            $this->tester->getDisplay()
        );
    }
}
