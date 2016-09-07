<?php

namespace Droid\Test\Plugin\Fs\Model\Fstab;

use PHPUnit_Framework_TestCase;

use Droid\Plugin\Fs\Model\Fstab\FstabLine;

class FstabLineTest extends PHPUnit_Framework_TestCase
{
    /**
     * @covers \Droid\Plugin\Fs\Model\Fstab\Fstabline::getMappingValues
     */
    public function testGetMappingValuesWillReturnValuesOfFilesystemAndMountpointFields()
    {
        $line = new FstabLine;

        $line
            ->setFieldValue('fileSystem', '/dev/sda1')
            ->setFieldValue('mountPoint', '/mnt/point')
        ;

        $this->assertSame(
            array('/dev/sda1', '/mnt/point'),
            $line->getMappingValues()
        );
    }

    /**
     * @covers \Droid\Plugin\Fs\Model\Fstab\Fstabline::setFieldValue
     */
    public function testSetFieldValueWithValuesEqualToOriginalsWillNotChangeValue()
    {
        $line = new FstabLine;

        $line
            ->set('/dev/sda1 /mnt/point ext3 rw 1 2')
            ->setFieldValue('fileSystem', '/dev/sda1')
        ;

        $this->assertSame(
            '/dev/sda1',
            $line->getFieldValue('fileSystem')
        );

        $this->assertFalse(
            $line->changed(),
            'The line is unchanged when its given values equal the original values.'
        );
    }

    /**
     * @covers \Droid\Plugin\Fs\Model\Fstab\Fstabline::setFieldValue
     */
    public function testSetFieldValueWitValueshDifferentFromOriginalsWillChangeValue()
    {
        $line = new FstabLine;

        $line
            ->set('/dev/sda1 /mnt/point ext3 rw 1 2')
            ->setFieldValue('fileSystem', '/dev/sdb2')
        ;

        $this->assertSame(
            '/dev/sdb2',
            $line->getFieldValue('fileSystem')
        );

        $this->assertTrue(
            $line->changed(),
            'The line is changed when the given values are different from the originals.'
        );
    }

    /**
     * @covers \Droid\Plugin\Fs\Model\Fstab\Fstabline::setFieldValue
     */
    public function testSetFieldValueWithValuesEqualToOriginalsAndDefaultsWillNotChangeValue()
    {
        $line = new FstabLine;

        $line
            ->set('/dev/sda1 /mnt/point ext3 rw 0 0')
            ->setFieldValue('dump', 0)
        ;

        $this->assertSame(
            0,
            $line->getFieldValue('dump')
        );

        $this->assertFalse(
            $line->changed(),
            'No change when given values are equal to the defaults and originals.'
        );
    }

    /**
     * @covers \Droid\Plugin\Fs\Model\Fstab\Fstabline::setFieldValue
     */
    public function testSetFieldValueWithValuesDifferentFromOriginalsAndEqualToDefaultsWillChangeValue()
    {
        $line = new FstabLine;

        $line
            ->set('/dev/sda1 /mnt/point ext3 rw 1 1')
            ->setFieldValue('pass', 0)
        ;

        $this->assertSame(
            0,
            $line->getFieldValue('pass')
        );

        $this->assertTrue(
            $line->changed(),
            'Optional values can be changed to values equal to the defaults.'
        );
    }

    /**
     * @covers \Droid\Plugin\Fs\Model\Fstab\Fstabline::setFieldValue
     */
    public function testSetFieldValueWithValuesEqualToDefaultsAndUnsetOriginalsWillNotSetValue()
    {
        $line = new FstabLine;

        $line
            ->set('/dev/sda1 /mnt/point ext3 rw')
            ->setFieldValue('dump', 0)
        ;

        $this->assertNull($line->getFieldValue('dump'));

        $this->assertFalse(
            $line->changed(),
            'No change when original optional values are unset and the given values equal the defaults.'
        );
    }

    /**
     * @dataProvider provideInvalidValues
     * @covers \Droid\Plugin\Fs\Model\Fstab\Fstabline::setFileSystem
     * @covers \Droid\Plugin\Fs\Model\Fstab\Fstabline::setMountPoint
     * @covers \Droid\Plugin\Fs\Model\Fstab\Fstabline::setFileSystemType
     * @covers \Droid\Plugin\Fs\Model\Fstab\Fstabline::setOptions
     * @covers \Droid\Plugin\Fs\Model\Fstab\Fstabline::setDump
     * @covers \Droid\Plugin\Fs\Model\Fstab\Fstabline::setPass
     * @param string $mutator
     * @param mixed $value
     * @param string $exceptionMessage
     */
    public function testMutatorWithInvalidValueWillThrowException(
        $mutator,
        $value,
        $exceptionMessage
    ) {
        $line = new FstabLine;

        $this->setExpectedException(
            '\InvalidArgumentException',
            $exceptionMessage
        );

        $line->$mutator($value);
    }

    public function provideInvalidValues()
    {
        return array(
            'Non-string fs' => array(
                'setFileSystem',
                1,
                'The "fileSystem" argument is not a non-empty string.',
            ),
            'Empty string fs' => array(
                'setFileSystem',
                '',
                'The "fileSystem" argument is not a non-empty string.',
            ),
            'Non-string mount point' => array(
                'setMountPoint',
                1,
                'The "mountPoint" argument is not a non-empty string.',
            ),
            'Empty string mount point' => array(
                'setMountPoint',
                '',
                'The "mountPoint" argument is not a non-empty string.',
            ),
            'Non-string fs type' => array(
                'setFileSystemType',
                1,
                'The "fileSystemType" argument is not a non-empty string.',
            ),
            'Empty string fs type' => array(
                'setFileSystemType',
                '',
                'The "fileSystemType" argument is not a non-empty string.',
            ),
            'Non-string options' => array(
                'setOptions',
                1,
                'The "options" argument is not a non-empty string.',
            ),
            'Empty string options' => array(
                'setOptions',
                '',
                'The "options" argument is not a non-empty string.',
            ),
            'Empty string dump' => array(
                'setDump',
                '',
                'The "dump" argument is not a numeric value.',
            ),
            'Non-numeric dump' => array(
                'setDump',
                'z',
                'The "dump" argument is not a numeric value.',
            ),
            'Empty string pass' => array(
                'setPass',
                '',
                'The "pass" argument is not a numeric value.',
            ),
            'Non-numeric pass' => array(
                'setPass',
                'z',
                'The "pass" argument is not a numeric value.',
            ),
        );
    }

    /**
     * @dataProvider provideValidValues
     * @covers \Droid\Plugin\Fs\Model\Fstab\Fstabline::setFileSystem
     * @covers \Droid\Plugin\Fs\Model\Fstab\Fstabline::setMountPoint
     * @covers \Droid\Plugin\Fs\Model\Fstab\Fstabline::setFileSystemType
     * @covers \Droid\Plugin\Fs\Model\Fstab\Fstabline::setOptions
     * @covers \Droid\Plugin\Fs\Model\Fstab\Fstabline::setDump
     * @covers \Droid\Plugin\Fs\Model\Fstab\Fstabline::setPass
     * @param string $mutator
     * @param mixed $value
     * @param string $fieldName
     */
    public function testMutatorWithValidValueWillSetValue(
        $mutator,
        $value,
        $fieldName
    ) {
        $line = new FstabLine;

        $line
            ->set('/dev/sda1 /mnt/point1 ext3 rw 1 2')
            ->$mutator($value)
        ;

        $this->assertSame(
            $value,
            $line->getFieldValue($fieldName),
            'Original line is modified.'
        );
    }

    public function provideValidValues()
    {
        return array(
            'Valid value for fileSystem' => array(
                'setFileSystem',
                '/dev/sda2',
                'fileSystem',
            ),
            'Valid value for mountPoint' => array(
                'setMountPoint',
                '/mnt/point2',
                'mountPoint',
            ),
            'Valid value for fileSystemType' => array(
                'setFileSystemType',
                'nfs',
                'fileSystemType',
            ),
            'Valid value for options' => array(
                'setOptions',
                'ro',
                'options',
            ),
            'Valid value for dump' => array(
                'setDump',
                0,
                'dump',
            ),
            'Valid value for pass' => array(
                'setPass',
                0,
                'pass',
            ),
        );
    }

    /**
     * @dataProvider provideInvalidOriginalLines
     * @covers \Droid\Plugin\Fs\Model\Fstab\Fstabline::set
     * @covers \Droid\Plugin\Fs\Model\Fstab\Fstabline::parse
     * @param string $originalLine
     * @param string $exceptionMessage
     */
    public function testSetWithInvalidOriginalLineWillThrowException(
        $originalLine,
        $exceptionMessage
    ) {
        $line = new FstabLine;

        $this->setExpectedException(
            'Droid\Plugin\Fs\Model\Fstab\FstabException',
            $exceptionMessage
        );

        $line->set($originalLine);
    }

    public function provideInvalidOriginalLines()
    {
        return array(
            'Insufficient fields' => array(
                '/dev/sda1 /mnt/point1 ext3',
                'Expected a well-formed line (of 4 or more fields)'
            ),
            'Non-numeric dump value' => array(
                '/dev/sda1 /mnt/point1 ext3 rw x',
                'Expected a well-formed line; "dump" is not numeric'
            ),
            'Non-numeric pass value' => array(
                '/dev/sda1 /mnt/point1 ext3 rw 0 x',
                'Expected a well-formed line; "pass" is not numeric'
            ),
        );
    }

    /**
     * @dataProvider provideValidOriginalLines
     * @covers \Droid\Plugin\Fs\Model\Fstab\Fstabline::set
     * @covers \Droid\Plugin\Fs\Model\Fstab\Fstabline::parse
     * @covers \Droid\Plugin\Fs\Model\Fstab\Fstabline::normaliseWhitespace
     * @param string $originalLine
     * @param string $exceptionMessage
     */
    public function testSetWithValidOriginalLineWillParseCorrectly(
        $originalLine,
        $expectedValues
    ) {
        $line = new FstabLine;

        $line->set($originalLine);

        $this->assertSame(
            $expectedValues,
            array(
                $line->getFieldValue('fileSystem'),
                $line->getFieldValue('mountPoint'),
                $line->getFieldValue('fileSystemType'),
                $line->getFieldValue('options'),
                $line->getFieldValue('dump'),
                $line->getFieldValue('pass'),
            )
        );
    }

    public function provideValidOriginalLines()
    {
        return array(
            'Sufficient fields' => array(
                '/dev/sda1 /mnt/point1 ext3 rw',
                array('/dev/sda1', '/mnt/point1', 'ext3', 'rw', null, null)
            ),
            'Sufficient fields and dump' => array(
                '/dev/sda1 /mnt/point1 ext3 rw 1',
                array('/dev/sda1', '/mnt/point1', 'ext3', 'rw', 1, null)
            ),
            'All fields' => array(
                '/dev/sda1 /mnt/point1 ext3 rw 1 1',
                array('/dev/sda1', '/mnt/point1', 'ext3', 'rw', 1, 1)
            ),
            'Mixed whitespace' => array(
                "/dev/sda1\t/mnt/point1 ext3 rw",
                array('/dev/sda1', '/mnt/point1', 'ext3', 'rw', null, null)
            ),
            'Much whitespace' => array(
                "/dev/sda1\t\t\t/mnt/point1      ext3  \t\t  rw       ",
                array('/dev/sda1', '/mnt/point1', 'ext3', 'rw', null, null)
            ),
        );
    }
}
