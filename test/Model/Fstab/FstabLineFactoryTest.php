<?php

namespace Droid\Test\Plugin\Model\Fstab;

use PHPUnit_Framework_TestCase;

use Droid\Plugin\Fs\Model\Fstab\FstabLineFactory;

class FstabLineFactoryTest extends PHPUnit_Framework_TestCase
{
    /**
     * @covers \Droid\Plugin\Fs\Model\Fstab\FstabLineFactory::makeLine
     */
    public function testMakeLineWillReturnInstanceofFstabLine()
    {
        $fac = new FstabLineFactory;
        $this->assertInstanceof(
            'Droid\Plugin\Fs\Model\Fstab\FstabLine',
            $fac->makeLine(),
            'Factory produces instances of FstabLine.'
        );
    }
}
