<?php

namespace Droid\Test\Plugin\Fs\Model\File;

use PHPUnit_Framework_TestCase;

use Droid\Plugin\Fs\Model\File\AbstractLine;

class AbstractLineTest extends PHPUnit_Framework_TestCase
{
    /**
     * @covers \Droid\Plugin\Fs\Model\File\AbstractLine::set
     */
    public function testSetWithEmptyLineWillNotParseLine()
    {
        $line = $this->getMockForAbstractClass(AbstractLine::class);

        $line
            ->expects($this->never())
            ->method('parse')
        ;

        $line->set('');
    }

    /**
     * @covers \Droid\Plugin\Fs\Model\File\AbstractLine::set
     */
    public function testSetWithCommentLineWillNotParseLine()
    {
        $line = $this->getMockForAbstractClass(AbstractLine::class);

        $line
            ->expects($this->never())
            ->method('parse')
        ;

        $line->set('# This is a comment');
    }

    /**
     * @covers \Droid\Plugin\Fs\Model\File\AbstractLine::set
     */
    public function testSetWithDataLineWillParseLine()
    {
        $line = $this->getMockForAbstractClass(AbstractLine::class);

        $line
            ->expects($this->once())
            ->method('parse')
            ->with($this->equalTo('some-field-value'))
        ;

        $line->set('some-field-value');
    }

        /**
     * @covers \Droid\Plugin\Fs\Model\File\AbstractLine::isData
     */
    public function testIsDataWithEmptyLineWillReturnFalse()
    {
        $line = $this->getMockForAbstractClass(AbstractLine::class);

        $this->assertFalse(
            $line->set('')->isData(),
            'An empty line is not considered to be data.'
        );
    }

    /**
     * @covers \Droid\Plugin\Fs\Model\File\AbstractLine::isData
     */
    public function testIsDataWithCommentLineWillReturnFalse()
    {
        $line = $this->getMockForAbstractClass(AbstractLine::class);

        $this->assertFalse(
            $line->set('# This is a comment')->isData(),
            'A comment line is not considered to be data.'
        );
    }

    /**
     * @covers \Droid\Plugin\Fs\Model\File\AbstractLine::isData
     */
    public function testIsDataWithCommentLineWillReturnTrue()
    {
        $line = $this->getMockForAbstractClass(AbstractLine::class);

        $line
            ->method('parse')
            ->willReturn(array('some-field-name' => 'some-field-value'))
        ;

        $this->assertTrue(
            $line->set('some-field-name = some-field-value')->isData(),
            'A data line is a line that was parsed.'
        );
    }

    /**
     * @covers \Droid\Plugin\Fs\Model\File\AbstractLine::setFieldValue
     * @covers \Droid\Plugin\Fs\Model\File\AbstractLine::getFieldValue
     */
    public function testSetFieldValueWithPreviouslyUnsetFieldNameWillSetFieldValue()
    {
        $line = $this->getMockForAbstractClass(AbstractLine::class);

        $line
            ->method('parse')
            ->willReturn(array('some-field-name' => 'some-field-value'))
        ;

        $this->assertNull(
            $line->getFieldValue('some-other-field'),
            'A value for the field has not yet been set.'
        );

        $line
            ->set('some-field-value')
            ->setFieldValue('some-other-field', 'a-value')
        ;

        $this->assertSame(
            'a-value',
            $line->getFieldValue('some-other-field'),
            'A value for the field has been set.'
        );
    }

    /**
     * @covers \Droid\Plugin\Fs\Model\File\AbstractLine::setFieldValue
     * @covers \Droid\Plugin\Fs\Model\File\AbstractLine::getFieldValue
     */
    public function testSetFieldValueWithPreviouslySetFieldNameAndDifferentValueWillSetFieldValue()
    {
        $line = $this->getMockForAbstractClass(AbstractLine::class);

        $line
            ->method('parse')
            ->willReturn(array('some-field-name' => 'some-field-value'))
        ;

        $line->set('some-field-value');

        $this->assertSame(
            'some-field-value',
            $line->getFieldValue('some-field-name'),
            'The value of the field is its original value.'
        );

        $line->setFieldValue('some-field-name', 'a-different-value');

        $this->assertSame(
            'a-different-value',
            $line->getFieldValue('some-field-name'),
            'A different value for the field has been set.'
        );
    }

    /**
     * @covers \Droid\Plugin\Fs\Model\File\AbstractLine::setFieldValue
     * @covers \Droid\Plugin\Fs\Model\File\AbstractLine::getFieldValue
     */
    public function testSetFieldValueWithPreviouslySetFieldNameAndTheSameValueWillNotSetFieldValue()
    {
        $line = $this->getMockForAbstractClass(AbstractLine::class);

        $line
            ->method('parse')
            ->willReturn(array('some-field-name' => 'some-field-value'))
        ;

        $line->set('some-field-value');

        $this->assertSame(
            'some-field-value',
            $line->getFieldValue('some-field-name'),
            'The value of the field is its original value.'
        );

        $line->setFieldValue('some-field-name', 'some-field-value');

        $this->assertSame(
            'some-field-value',
            $line->getFieldValue('some-field-name'),
            'The value of the field is its unchanged original value.'
        );
    }

    /**
     * @covers \Droid\Plugin\Fs\Model\File\AbstractLine::changed
     */
    public function testChangedWithoutOriginalValuesWillReturnFalse()
    {
        $line = $this->getMockForAbstractClass(AbstractLine::class);

        $line->setFieldValue('some-field-name', 'some-field-value');

        $this->assertFalse(
            $line->changed(),
            'The line has not changed when there are zero original parsed values'
        );
    }

    /**
     * @covers \Droid\Plugin\Fs\Model\File\AbstractLine::changed
     */
    public function testChangedWithOriginalValuesWillReturnFalse()
    {
        $line = $this->getMockForAbstractClass(AbstractLine::class);

        $line
            ->method('parse')
            ->willReturn(array('some-field-name' => 'some-field-value'))
        ;

        $line->set('some-field-value');

        $this->assertFalse(
            $line->changed(),
            'The line has not changed when there are zero, given values different from the originals'
        );
    }

    /**
     * @covers \Droid\Plugin\Fs\Model\File\AbstractLine::changed
     */
    public function testChangedWithOriginalValuesAndTheSameGivenValuesWillReturnFalse()
    {
        $line = $this->getMockForAbstractClass(AbstractLine::class);

        $line
            ->method('parse')
            ->willReturn(array('some-field-name' => 'some-field-value'))
        ;

        $line
            ->set('some-field-value')
            ->setFieldValue('some-field-name', 'some-field-value')
        ;

        $this->assertFalse(
            $line->changed(),
            'The line has not changed when there are zero, given values different from the originals'
        );
    }

    /**
     * @covers \Droid\Plugin\Fs\Model\File\AbstractLine::changed
     */
    public function testChangedWithOriginalValuesAndDifferentGivenValuesWillReturnTrue()
    {
        $line = $this->getMockForAbstractClass(AbstractLine::class);

        $line
            ->method('parse')
            ->willReturn(array('some-field-name' => 'some-field-value'))
        ;

        $line
            ->set('some-field-value')
            ->setFieldValue('some-field-name', 'a-different-field-value')
        ;

        $this->assertTrue(
            $line->changed(),
            'The line has changed when there given values different from the originals'
        );
    }

    /**
     * @covers \Droid\Plugin\Fs\Model\File\AbstractLine::changed
     */
    public function testChangedWithOriginalValuesAndAdditionalGivenValuesWillReturnTrue()
    {
        $line = $this->getMockForAbstractClass(AbstractLine::class);

        $line
            ->method('parse')
            ->willReturn(array('some-field-name' => 'some-field-value'))
        ;

        $line
            ->set('some-field-value')
            ->setFieldValue('an-additional-field-name', 'an-additional-field-value')
        ;

        $this->assertTrue(
            $line->changed(),
            'The line has changed when there are given values in addition to the originals'
        );
    }

    /**
     * @covers \Droid\Plugin\Fs\Model\File\AbstractLine::update
     */
    public function testUpdateWithoutGivenValuesWillDoNothing()
    {
        $line = $this->getMockForAbstractClass(AbstractLine::class);

        $lineToUpdate = $this->getMockForAbstractClass(AbstractLine::class);

        $line
            ->method('parse')
            ->willReturn(array('some-field-name' => 'some-field-value'))
        ;

        $lineToUpdate
            ->expects($this->never())
            ->method('setFieldValue')
        ;

        $line
            ->set('some-field-value')
            ->update($lineToUpdate)
        ;
    }

    /**
     * @covers \Droid\Plugin\Fs\Model\File\AbstractLine::update
     */
    public function testUpdateWillReturnSelf()
    {
        $line = $this->getMockForAbstractClass(AbstractLine::class);

        $lineToUpdate = $this->getMockForAbstractClass(AbstractLine::class);

        $line
            ->method('parse')
            ->willReturn(array('some-field-name' => 'some-field-value'))
        ;

        $line->set('some-field-value');
        $this->assertSame($line, $line->update($lineToUpdate));
    }

    /**
     * @covers \Droid\Plugin\Fs\Model\File\AbstractLine::update
     */
    public function testUpdateWithGivenValuesWillUpdateTheSuppliedLine()
    {
        $line = $this->getMockForAbstractClass(AbstractLine::class);

        $lineToUpdate = $this
            ->getMockBuilder(AbstractLine::class)
            ->setMethods(array('setFieldValue'))
            ->getMockForAbstractClass()
        ;

        $lineToUpdate
            ->expects($this->once())
            ->method('setFieldValue')
            ->with(
                $this->equalTo('some-field-name'),
                $this->equalTo('some-field-value')
            )
        ;

        $line
            ->setFieldValue('some-field-name', 'some-field-value')
            ->update($lineToUpdate)
        ;
    }

    /**
     * @covers \Droid\Plugin\Fs\Model\File\AbstractLine::__toString
     */
    public function testToStringWithInitialLineWillReturnEmptyString()
    {
        $line = $this->getMockForAbstractClass(AbstractLine::class);

        $this->assertSame(
            '',
            (string) $line,
            'An empty string represents an initialised line'
        );
    }

    /**
     * @covers \Droid\Plugin\Fs\Model\File\AbstractLine::__toString
     */
    public function testToStringWithEmptyLineWillReturnEmptyString()
    {
        $line = $this->getMockForAbstractClass(AbstractLine::class);

        $this->assertSame(
            '',
            (string) $line->set(''),
            'An empty string is returned when the original line was empty'
        );
    }

    /**
     * @covers \Droid\Plugin\Fs\Model\File\AbstractLine::__toString
     */
    public function testToStringWithCommentLineWillReturnCommentLine()
    {
        $line = $this->getMockForAbstractClass(AbstractLine::class);

        $this->assertSame(
            '# This is a comment',
            (string) $line->set('# This is a comment'),
            'A comment is returned unchanged when the original line was a comment'
        );
    }

    /**
     * @covers \Droid\Plugin\Fs\Model\File\AbstractLine::__toString
     */
    public function testToStringWithOnlyOriginalValuesWillReturnOriginalLine()
    {
        $line = $this->getMockForAbstractClass(AbstractLine::class);

        $line
            ->method('parse')
            ->willReturn(array('some-field-name' => 'some-field-value'))
        ;

        $this->assertSame(
            'some-field-value',
            (string) $line->set('some-field-value'),
            'The original line is returned unchanged when there has been no change to the line'
        );
    }

    /**
     * @covers \Droid\Plugin\Fs\Model\File\AbstractLine::__toString
     */
    public function testToStringWithOnlyGivenValuesWillReturnGivenValuesAsFormattedString()
    {
        $line = $this->getMockForAbstractClass(AbstractLine::class);

        $this->assertSame(
            'some-field-value',
            (string) $line->setFieldValue('some-field-name', 'some-field-value'),
            'The given values are formed into a string when there was no original line'
        );
    }

    /**
     * @covers \Droid\Plugin\Fs\Model\File\AbstractLine::setFieldSeparator
     * @covers \Droid\Plugin\Fs\Model\File\AbstractLine::__toString
     * @covers \Droid\Plugin\Fs\Model\File\AbstractLine::format
     */
    public function testToStringWithOriginalValuesAndGivenValuesWillReturnMergedValuesAsFormattedString()
    {
        $line = $this->getMockForAbstractClass(AbstractLine::class);

        $line
            ->method('parse')
            ->willReturn(
                array(
                    'some-field-name' => 'some-field-value',
                    'another-field-name' => 'another-field-value',
                )
            )
        ;

        $line
            ->setFieldSeparator('=')
            ->set('some-field-value=another-field-value')
            ->setFieldValue('some-field-name', 'a-different-field-value')
        ;

        $this->assertSame(
            'a-different-field-value=another-field-value',
            (string) $line,
            'Values in the original line are replaced with given values'
        );
    }
}
