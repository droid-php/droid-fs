<?php

namespace Droid\Test\Plugin\Fs\Model\File;

use PHPUnit_Framework_TestCase;

use Droid\Plugin\Fs\Model\File\NameValueLine;

class NameValueLineTest extends PHPUnit_Framework_TestCase
{
    /**
     * @covers \Droid\Plugin\Fs\Model\File\NameValueLine::getMappingValues
     */
    public function testGetMappingValuesWillReturnTheNameOfTheOption()
    {
        $line = new NameValueLine;

        $line
            ->setFieldValue(NameValueLine::FIELD_NAME, 'some-option-name')
            ->setFieldValue(NameValueLine::FIELD_VALUE, 'some-option-value')
        ;

        $this->assertSame(
            array('some-option-name'),
            $line->getMappingValues()
        );
    }

    /**
     * @dataProvider provideInvalidOriginalLines
     * @covers \Droid\Plugin\Fs\Model\File\NameValueLine::parse
     * @param string $originalLine
     * @param string $exceptionMessage
     */
    public function testSetWithInvalidOriginalLineWillThrowException(
        $separator,
        $originalLine,
        $exceptionMessage
    ) {
        $line = new NameValueLine;

        $this->setExpectedException(
            '\DomainException',
            $exceptionMessage
        );

        $line
            ->setFieldSeparator($separator)
            ->set($originalLine)
        ;
    }

    public function provideInvalidOriginalLines()
    {
        return array(
            'Insufficient fields' => array(
                ' ',
                'SomeOptionName',
                'Expected a well-formed line of two fields "name value"'
            ),
            'Unexepected Separator' => array(
                '=',
                'SomeOptionName SomeValue',
                'Expected a well-formed line of two fields "name=value"'
            ),
            'Surplus fields' => array(
                ' ',
                'SomeOptionName SomeValue SomeOtherValue',
                'Expected a well-formed line of two fields "name value"'
            ),
        );
    }

    /**
     * @dataProvider provideValidOriginalLines
     * @covers \Droid\Plugin\Fs\Model\File\NameValueLine::parse
     * @covers \Droid\Plugin\Fs\Model\File\NameValueLine::normaliseWhitespace
     * @param string $separator
     * @param string $originalLine
     * @param string $expectedValues
     */
    public function testSetWithValidOriginalLineWillParseCorrectly(
        $separator,
        $originalLine,
        $expectedValues
    ) {
        $line = new NameValueLine;

        $line
            ->setFieldSeparator($separator)
            ->set($originalLine)
        ;

        $this->assertSame(
            $expectedValues,
            array(
                $line->getFieldValue(NameValueLine::FIELD_NAME),
                $line->getFieldValue(NameValueLine::FIELD_VALUE),
            )
        );
    }

    public function provideValidOriginalLines()
    {
        return array(
            'Space separated Name Value pair' => array(
                ' ',
                'SomeOption SomeValue',
                array('SomeOption', 'SomeValue')
            ),
            'Much space separated Name Value pair' => array(
                ' ',
                'SomeOption                      SomeValue',
                array('SomeOption', 'SomeValue')
            ),
            'Tab separated Name Value pair' => array(
                "\t",
                "SomeOption\tSomeValue",
                array('SomeOption', 'SomeValue')
            ),
            'Colon separated Name Value pair' => array(
                ':',
                'SomeOption:SomeValue',
                array('SomeOption', 'SomeValue')
            ),
            'Equals sign separated Name Value pair' => array(
                '=',
                'SomeOption=SomeValue',
                array('SomeOption', 'SomeValue')
            ),
            'Equals sign and space separated Name Value pair' => array(
                ' = ',
                'SomeOption = SomeValue',
                array('SomeOption', 'SomeValue')
            ),
            'Equals sign and much space separated Name Value pair' => array(
                ' = ',
                "SomeOption\t\t \t\t=          \t        SomeValue",
                array('SomeOption', 'SomeValue')
            ),
        );
    }
}
