<?php

namespace Droid\Test\Plugin\Model\Fstab;

use org\bovigo\vfs\vfsStream;
use PHPUnit_Framework_TestCase;

use Droid\Plugin\Fs\Model\Fstab\FstabBuilder;
use Droid\Plugin\Fs\Model\Fstab\FstabLineFactory;

class FstabBuilderTest extends PHPUnit_Framework_TestCase
{
    protected $fstabFile;

    protected function setUp()
    {
        $root = vfsStream::setup('etc');
        $this->fstabFile = vfsStream::newFile('fstab')->at($root);
    }

    /**
     * @covers \Droid\Plugin\Fs\Model\Fstab\FstabBuilder::buildFstab
     */
    public function testBuildFstabWillReturnInstanceofFstab()
    {
        $builder = new FstabBuilder(new FstabLineFactory);

        $this->assertInstanceof(
            'Droid\Plugin\Fs\Model\Fstab\Fstab',
            $builder->buildFstab($this->fstabFile->url()),
            'Builder produces instances of Fstab.'
        );
    }
}
