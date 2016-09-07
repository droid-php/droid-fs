<?php

namespace Droid\Test\Plugin\Fs\Model\File;

use PHPUnit_Framework_TestCase;

use Droid\Plugin\Fs\Model\File\LineFactory;
use Droid\Plugin\Fs\Model\File\NameValueLine;

class LineFactoryTest extends PHPUnit_Framework_TestCase
{
    /**
     * @covers \Droid\Plugin\Fs\Model\File\LineFactory::__construct
     * @covers \Droid\Plugin\Fs\Model\File\LineFactory::makeLine
     */
    public function testMakeLineWillReturnInstanceofTheSuppliedLineClass()
    {
        $fac = new LineFactory(NameValueLine::class);
        $this->assertInstanceof(
            NameValueLine::class,
            $fac->makeLine(),
            'Factory produces instances of the supplied line class name.'
        );
    }

    /**
     * @covers \Droid\Plugin\Fs\Model\File\LineFactory::__construct
     * @covers \Droid\Plugin\Fs\Model\File\LineFactory::getFieldSeparator
     * @covers \Droid\Plugin\Fs\Model\File\LineFactory::setFieldSeparator
     */
    public function testFieldSeparatorAccessAndMutation()
    {
        $fac = new LineFactory(NameValueLine::class, 'some-sep');
        $this->assertSame('some-sep', $fac->getFieldSeparator());
        $fac->setFieldSeparator('other-sep');
        $this->assertSame('other-sep', $fac->getFieldSeparator());
    }
}
