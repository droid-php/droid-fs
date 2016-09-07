<?php

namespace Droid\Test\Plugin\Fs\Model\File;

use org\bovigo\vfs\vfsStream;
use PHPUnit_Framework_TestCase;

use Droid\Plugin\Fs\Model\File\FileFactory;
use Droid\Plugin\Fs\Model\File\LineBasedFile;
use Droid\Plugin\Fs\Model\File\LineFactory;

class FileFactoryTest extends PHPUnit_Framework_TestCase
{
    protected $file;
    protected $lineFactory;

    protected function setUp()
    {
        $root = vfsStream::setup('etc');
        $this->file = vfsStream::newFile('fstab')->at($root);
        $this->lineFactory = $this
            ->getMockBuilder(LineFactory::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;
    }

    /**
     * @covers \Droid\Plugin\Fs\Model\File\FileFactory::__construct
     * @covers \Droid\Plugin\Fs\Model\File\FileFactory::makeFile
     */
    public function testMakeFileWillReturnInstanceofFile()
    {
        $fileFac = new FileFactory(LineBasedFile::class, $this->lineFactory);

        $this->assertInstanceof(
            LineBasedFile::class,
            $fileFac->makeFile($this->file->url()),
            'FileFactory produces instances of the supplied file class name.'
        );
    }

    /**
     * @covers \Droid\Plugin\Fs\Model\File\FileFactory::getLineFactory
     */
    public function testGetLineFactoryWillReturnLineFactoryGivenToConstructor()
    {
        $fileFac = new FileFactory(LineBasedFile::class, $this->lineFactory);

        $this->assertSame(
            $this->lineFactory,
            $fileFac->getLineFactory()
        );
    }
}
