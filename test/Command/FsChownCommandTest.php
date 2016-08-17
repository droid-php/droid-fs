<?php

namespace Droid\Test\Plugin\Fs\Command;

use \RuntimeException;

use org\bovigo\vfs\vfsStream;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\ProcessBuilder;

use Droid\Plugin\Fs\Command\FsChownCommand;
use Droid\Plugin\Fs\Service\AclObjectLookupInterface;

class FsChownCommandTest extends \PHPUnit_Framework_TestCase
{
    protected $app;
    protected $lookup;
    protected $process;
    protected $processBuilder;
    protected $tester;
    protected $vfs;

    protected function setUp()
    {
        $this->process = $this
            ->getMockBuilder(Process::class)
            ->disableOriginalConstructor()
            ->setMethods(array('run', 'getErrorOutput', 'getExitCode'))
            ->getMock()
        ;
        $this->processBuilder = $this
            ->getMockBuilder(ProcessBuilder::class)
            ->setMethods(array('setArguments', 'getProcess'))
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
        $this->lookup = $this
            ->getMockBuilder(AclObjectLookupInterface::class)
            ->getMock()
        ;

        $command = new FsChownCommand($this->lookup, $this->processBuilder);

        $this->app = new Application;
        $this->app->add($command);

        $this->tester = new CommandTester($command);

        $this->vfs = vfsStream::setup();
    }

    protected function tearDown()
    {
        clearstatcache();
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage The file "vfs://root/some_file" does not exist
     */
    public function testChownThrowsExceptionWhenTheFileDoesNotExist()
    {
        $this->tester->execute(array(
            'command' => $this->app->find('fs:chown')->getName(),
            'file' => vfsStream::url('root/some_file'),
            'user' => 'a_user',
            'group' => 'a_group',
        ));
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage Failed to determine user id for a user named "a_user"
     */
    public function testChownThrowsExceptionWhenUidIsNotFound()
    {
        vfsStream::newFile('some_file')->at($this->vfs);

        $this
            ->lookup
            ->expects($this->once())
            ->method('userId')
            ->with('a_user')
            ->willReturn(null)
        ;
        $this
            ->lookup
            ->expects($this->never())
            ->method('groupId')
        ;

        $this->tester->execute(array(
            'command' => $this->app->find('fs:chown')->getName(),
            'file' => vfsStream::url('root/some_file'),
            'user' => 'a_user',
            'group' => 'a_group',
        ));
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage Failed to determine group id for a group named "a_group"
     */
    public function testChownThrowsExceptionWhenGidIsNotFound()
    {
        vfsStream::newFile('some_file')->at($this->vfs);

        $this
            ->lookup
            ->expects($this->once())
            ->method('userId')
            ->with('a_user')
            ->willReturn(1000)
        ;
        $this
            ->lookup
            ->expects($this->once())
            ->method('groupId')
            ->with('a_group')
            ->willReturn(null)
        ;

        $this->tester->execute(array(
            'command' => $this->app->find('fs:chown')->getName(),
            'file' => vfsStream::url('root/some_file'),
            'user' => 'a_user',
            'group' => 'a_group',
        ));
    }

    public function testChownDoesNothingWhenSuppliedOwnershipsMatchExisting()
    {
        $user = 'a_user';
        $group = 'a_group';

        vfsStream::newFile('some_file')
            ->at($this->vfs)
            ->setContent('hello!')
            ->chown($user)
            ->chgrp($group)
        ;
        $stat = stat(vfsStream::url('root/some_file'));

        $this
            ->lookup
            ->expects($this->once())
            ->method('userId')
            ->with('a_user')
            ->willReturn($stat['uid'])
        ;

        $this
            ->lookup
            ->expects($this->once())
            ->method('groupId')
            ->with('a_group')
            ->willReturn($stat['gid'])
        ;

        $this->tester->execute(array(
            'command' => $this->app->find('fs:chown')->getName(),
            'file' => vfsStream::url('root/some_file'),
            'user' => $user,
            'group' => $group,
        ));

        $this->assertRegExp(
            '/^The file "[^"]*" already has the supplied ownership. Nothing to do/',
            $this->tester->getDisplay()
        );
    }

    public function testChownChangesOnlyUserOwnershipWhenGroupOwnershipMatchesArg()
    {
        # set up a file to chown and
        vfsStream::newFile('some_file')
            ->at($this->vfs)
            ->setContent('hello!')
            ->chown('a_user')
            ->chgrp('a_group')
        ;
        $path = vfsStream::url('root/some_file');
        $stat = stat($path);
        $original_uid = $stat['uid'];
        $original_gid = $stat['gid'];

        # set up user and group who will become owner
        $expected_user = 'other_user';
        $expected_uid = $original_uid + 1; # the new owner has a different uid
        $expected_group = 'a_group';       # the group owner isn't changing

        $this
            ->lookup
            ->expects($this->once())
            ->method('userId')
            ->with($expected_user)
            ->willReturn($expected_uid)
        ;
        $this
            ->lookup
            ->expects($this->once())
            ->method('groupId')
            ->with($expected_group)
            ->willReturn($original_gid) # same as the present file owner gid
        ;
        $this
            ->processBuilder
            ->expects($this->once())
            ->method('setArguments')
            ->with(
                array(
                    'sudo',
                    'chown',
                    sprintf('%s:%s', $expected_user, $expected_group),
                    $path
                )
            )
        ;
        $this
            ->process
            ->method('run')
            ->willReturn(0)
        ;

        $this->tester->execute(array(
            'command' => $this->app->find('fs:chown')->getName(),
            'file' => $path,
            'user' => $expected_user,
            'group' => $expected_group,
        ));

        $this->assertRegExp(
            '/^The file "[^"]*" is already owned by group "a_group". I will not change group ownership/',
            $this->tester->getDisplay()
        );
        $this->assertRegExp(
            '/Chowned file "[^"]*"/',
            $this->tester->getDisplay()
        );
    }

    public function testChownChangesOnlyGroupOwnershipWhenUserOwnershipMatchesArg()
    {
        vfsStream::newFile('some_file')
            ->at($this->vfs)
            ->setContent('hello!')
            ->chown('a_user')
            ->chgrp('a_group')
        ;
        $path = vfsStream::url('root/some_file');
        $stat = stat($path);
        $original_uid = $stat['uid'];
        $original_gid = $stat['gid'];

        $expected_user = 'a_user';         # the user owner isn't changing
        $expected_group = 'other_group';
        $expected_gid = $original_gid + 1; # the group owner has a different gid

        $this
            ->lookup
            ->expects($this->once())
            ->method('userId')
            ->with($expected_user)
            ->willReturn($original_uid) # same as the present file owner gid
        ;
        $this
            ->lookup
            ->expects($this->once())
            ->method('groupId')
            ->with($expected_group)
            ->willReturn($expected_gid)
        ;
        $this
            ->processBuilder
            ->expects($this->once())
            ->method('setArguments')
            ->with(
                array(
                    'sudo',
                    'chown',
                    sprintf('%s:%s', $expected_user, $expected_group),
                    $path
                )
            )
        ;
        $this
            ->process
            ->method('run')
            ->willReturn(0)
        ;

        $this->tester->execute(array(
            'command' => $this->app->find('fs:chown')->getName(),
            'file' => $path,
            'user' => $expected_user,
            'group' => $expected_group,
        ));

        $this->assertRegExp(
            '/^The file "[^"]*" is already owned by user "a_user". I will not change user ownership/',
            $this->tester->getDisplay()
        );
        $this->assertRegExp(
            '/Chowned file "[^"]*"/',
            $this->tester->getDisplay()
        );
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage Failed to change ownership of file
     */
    public function testChownThrowsExceptionWhenItFailsToChangesOwnership()
    {
        vfsStream::newFile('some_file')
            ->at($this->vfs)
            ->setContent('hello!')
            ->chown('a_user')
            ->chgrp('a_group')
        ;
        $path = vfsStream::url('root/some_file');
        $stat = stat($path);
        $original_uid = $stat['uid'];
        $original_gid = $stat['gid'];

        $expected_user = 'a_user';
        $expected_group = 'other_group';
        $expected_uid = $original_uid + 1;
        $expected_gid = $original_gid + 1;

        $this
            ->lookup
            ->expects($this->once())
            ->method('userId')
            ->with($expected_user)
            ->willReturn($expected_uid)
        ;
        $this
            ->lookup
            ->expects($this->once())
            ->method('groupId')
            ->with($expected_group)
            ->willReturn($expected_gid)
        ;
        $this
            ->processBuilder
            ->expects($this->once())
            ->method('setArguments')
            ->with(
                array(
                    'sudo',
                    'chown',
                    sprintf('%s:%s', $expected_user, $expected_group),
                    $path
                )
            )
        ;
        $this
            ->process
            ->method('run')
            ->willReturn(1)
        ;
        $this
            ->process
            ->method('getErrorOutput')
            ->willReturn('For some reason or other.')
        ;

        $this->tester->execute(array(
            'command' => $this->app->find('fs:chown')->getName(),
            'file' => $path,
            'user' => $expected_user,
            'group' => $expected_group,
        ));
    }

    public function testChownChangesOwnership()
    {
        vfsStream::newFile('some_file')
            ->at($this->vfs)
            ->setContent('hello!')
            ->chown('a_user')
            ->chgrp('a_group')
        ;
        $path = vfsStream::url('root/some_file');
        $stat = stat($path);
        $original_uid = $stat['uid'];
        $original_gid = $stat['gid'];

        $expected_user = 'a_user';
        $expected_group = 'other_group';
        $expected_uid = $original_uid + 1;
        $expected_gid = $original_gid + 1;

        $this
            ->lookup
            ->expects($this->once())
            ->method('userId')
            ->with($expected_user)
            ->willReturn($expected_uid)
        ;
        $this
            ->lookup
            ->expects($this->once())
            ->method('groupId')
            ->with($expected_group)
            ->willReturn($expected_gid)
        ;
        $this
            ->processBuilder
            ->expects($this->once())
            ->method('setArguments')
            ->with(
                array(
                    'sudo',
                    'chown',
                    sprintf('%s:%s', $expected_user, $expected_group),
                    $path
                )
            )
        ;
        $this
            ->process
            ->method('run')
            ->willReturn(0)
        ;

        $this->tester->execute(array(
            'command' => $this->app->find('fs:chown')->getName(),
            'file' => $path,
            'user' => $expected_user,
            'group' => $expected_group,
        ));

        $this->assertRegExp(
            '/^Chowned file "[^"]*"/',
            $this->tester->getDisplay()
        );
    }

    public function testChownDoesNotChangeOwnershipInCheckMode()
    {
        vfsStream::newFile('some_file')
            ->at($this->vfs)
            ->setContent('hello!')
            ->chown('a_user')
            ->chgrp('a_group')
        ;
        $path = vfsStream::url('root/some_file');
        $stat = stat($path);
        $original_uid = $stat['uid'];
        $original_gid = $stat['gid'];

        $expected_user = 'a_user';
        $expected_group = 'other_group';
        $expected_uid = $original_uid + 1;
        $expected_gid = $original_gid + 1;

        $this
            ->lookup
            ->expects($this->once())
            ->method('userId')
            ->with($expected_user)
            ->willReturn($expected_uid)
        ;
        $this
            ->lookup
            ->expects($this->once())
            ->method('groupId')
            ->with($expected_group)
            ->willReturn($expected_gid)
        ;
        $this
            ->processBuilder
            ->expects($this->never())
            ->method('setArguments')
        ;
        $this
            ->process
            ->expects($this->never())
            ->method('run')
        ;

        $this->tester->execute(array(
            'command' => $this->app->find('fs:chown')->getName(),
            'file' => $path,
            'user' => $expected_user,
            'group' => $expected_group,
            '--check' => true
        ));

        $this->assertRegExp(
            '/^I would chown file "[^"]*"/',
            $this->tester->getDisplay()
        );
    }
}
