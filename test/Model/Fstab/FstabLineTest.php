<?php

namespace Droid\Test\Plugin\Model\Fstab;

use PHPUnit_Framework_TestCase;

use Droid\Plugin\Fs\Model\Fstab\FstabLine;

class FstabLineTest extends PHPUnit_Framework_TestCase
{
    /**
     * @covers \Droid\Plugin\Fs\Model\Fstab\Fstabline::setContent
     * @covers \Droid\Plugin\Fs\Model\Fstab\Fstabline::parse
     * @covers \Droid\Plugin\Fs\Model\Fstab\Fstabline::setFileSystem
     * @covers \Droid\Plugin\Fs\Model\Fstab\Fstabline::setMountPoint
     * @covers \Droid\Plugin\Fs\Model\Fstab\Fstabline::setFileSystemType
     * @covers \Droid\Plugin\Fs\Model\Fstab\Fstabline::setOptions
     * @covers \Droid\Plugin\Fs\Model\Fstab\Fstabline::setDump
     * @covers \Droid\Plugin\Fs\Model\Fstab\Fstabline::setPass
     * @covers \Droid\Plugin\Fs\Model\Fstab\Fstabline::setValue
     * @covers \Droid\Plugin\Fs\Model\Fstab\Fstabline::changed
     */
    public function testChangedWithValuesEqualToOriginalsWillReturnFalse()
    {
        $line = new FstabLine;

        $line->setContent('/dev/sda1 /mnt/point ext3 rw 1 2');
        $line
            ->setFileSystem('/dev/sda1')
            ->setMountPoint('/mnt/point')
            ->setFileSystemType('ext3')
            ->setOptions('rw')
            ->setDump(1)
            ->setPass(2)
        ;

        $this->assertFalse(
            $line->changed(),
            'The line is unchanged when its given values equal the original values.'
        );
    }

    /**
     * @covers \Droid\Plugin\Fs\Model\Fstab\Fstabline::setContent
     * @covers \Droid\Plugin\Fs\Model\Fstab\Fstabline::parse
     * @covers \Droid\Plugin\Fs\Model\Fstab\Fstabline::setFileSystem
     * @covers \Droid\Plugin\Fs\Model\Fstab\Fstabline::setMountPoint
     * @covers \Droid\Plugin\Fs\Model\Fstab\Fstabline::setFileSystemType
     * @covers \Droid\Plugin\Fs\Model\Fstab\Fstabline::setOptions
     * @covers \Droid\Plugin\Fs\Model\Fstab\Fstabline::setDump
     * @covers \Droid\Plugin\Fs\Model\Fstab\Fstabline::setPass
     * @covers \Droid\Plugin\Fs\Model\Fstab\Fstabline::setValue
     * @covers \Droid\Plugin\Fs\Model\Fstab\Fstabline::changed
     */
    public function testChangedWitValueshDifferentFromOriginalsWillReturnTrue()
    {
        $line = new FstabLine;

        $line->setContent('/dev/sda1 /mnt/point ext3 rw 1 2');
        $line
            ->setFileSystem('/dev/sda1')
            ->setMountPoint('/mnt/foo')
            ->setFileSystemType('nfs')
            ->setOptions('ro')
            ->setDump(2)
            ->setPass(1)
        ;

        $this->assertTrue(
            $line->changed(),
            'The line is changed when the given values are different from the originals.'
        );
    }

    /**
     * @covers \Droid\Plugin\Fs\Model\Fstab\Fstabline::setContent
     * @covers \Droid\Plugin\Fs\Model\Fstab\Fstabline::parse
     * @covers \Droid\Plugin\Fs\Model\Fstab\Fstabline::setDump
     * @covers \Droid\Plugin\Fs\Model\Fstab\Fstabline::setPass
     * @covers \Droid\Plugin\Fs\Model\Fstab\Fstabline::setValue
     * @covers \Droid\Plugin\Fs\Model\Fstab\Fstabline::changed
     */
    public function testChangedWithValuesEqualToOriginalsAndDefaultsWillReturnFalse()
    {
        $line = new FstabLine;

        $line->setContent('/dev/sda1 /mnt/point ext3 rw 0 0');
        $line
            ->setDump(0)
            ->setPass(0)
        ;

        $this->assertFalse(
            $line->changed(),
            'No change when given values are equal to the defaults and originals.'
        );
    }

    /**
     * @covers \Droid\Plugin\Fs\Model\Fstab\Fstabline::setContent
     * @covers \Droid\Plugin\Fs\Model\Fstab\Fstabline::parse
     * @covers \Droid\Plugin\Fs\Model\Fstab\Fstabline::setDump
     * @covers \Droid\Plugin\Fs\Model\Fstab\Fstabline::setPass
     * @covers \Droid\Plugin\Fs\Model\Fstab\Fstabline::setValue
     * @covers \Droid\Plugin\Fs\Model\Fstab\Fstabline::changed
     */
    public function testChangedWithValuesDifferentFromOriginalsAndEqualToDefaultsWillReturnTrue()
    {
        $line = new FstabLine;

        $line->setContent('/dev/sda1 /mnt/point ext3 rw 1 1');
        $line
            ->setDump(0)
            ->setPass(0)
        ;

        $this->assertTrue(
            $line->changed(),
            'Optional values can be changed to values equal to the defaults.'
        );
    }

    /**
     * @covers \Droid\Plugin\Fs\Model\Fstab\Fstabline::setContent
     * @covers \Droid\Plugin\Fs\Model\Fstab\Fstabline::parse
     * @covers \Droid\Plugin\Fs\Model\Fstab\Fstabline::setDump
     * @covers \Droid\Plugin\Fs\Model\Fstab\Fstabline::setPass
     * @covers \Droid\Plugin\Fs\Model\Fstab\Fstabline::setValue
     * @covers \Droid\Plugin\Fs\Model\Fstab\Fstabline::changed
     */
    public function testChangedWithValuesEqualToDefaultsAndUnsetOriginalsWillReturnFalse()
    {
        $line = new FstabLine;

        $line->setContent('/dev/sda1 /mnt/point ext3 rw');
        $line
            ->setDump(0)
            ->setPass(0)
        ;

        $this->assertFalse(
            $line->changed(),
            'No change when original optional values are unset and the given values equal the defaults.'
        );
    }

    /**
     * @covers \Droid\Plugin\Fs\Model\Fstab\Fstabline::changed
     */
    public function testChangedWithZeroOriginalValuesWillReturnFalse()
    {
        $line = new FstabLine;

        $this->assertFalse(
            $line->setFileSystem('/dev/sda1')->changed(),
            'No change when setContent was never called.'
        );
    }

    /**
     * @covers \Droid\Plugin\Fs\Model\Fstab\Fstabline::setContent
     * @covers \Droid\Plugin\Fs\Model\Fstab\Fstabline::parse
     * @covers \Droid\Plugin\Fs\Model\Fstab\Fstabline::changed
     */
    public function testChangedWithNonFilesystemOriginalLinesWillReturnFalse()
    {
        $line = new FstabLine;

        $this->assertFalse(
            $line->setContent('')->setFileSystem('/dev/sda1')->changed(),
            'No change when original line was an empty line.'
        );

        $this->assertFalse(
            $line->setContent('# A comment')->setFileSystem('/dev/sda1')->changed(),
            'No change when original optional line was a comment line.'
        );
    }

    /**
     * @covers \Droid\Plugin\Fs\Model\Fstab\Fstabline::isParsedFileSystemInfo
     */
    public function testIsParsedFileSystemInfo()
    {
        $line = new FstabLine;

        $this->assertFalse(
            $line->setFileSystem('/dev/sda1')->isParsedFileSystemInfo(),
            'Is not "parsed filesystem info" if there was no parsed filesystem line.'
        );

        $this->assertFalse(
            $line->setContent('')->isParsedFileSystemInfo(),
            'Is not "parsed filesystem info" if the original line was not a filesystem line.'
        );

        $this->assertTrue(
            $line->setContent('/dev/sda1 /mnt/point1 ext3 rw')->isParsedFileSystemInfo(),
            'Is "parsed filesystem info" when a filesystem info line was parsed.'
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
    public function testMutatorWithInvalidValueThrowsException(
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
     * @dataProvider provideInvalidOriginalLines
     * @covers \Droid\Plugin\Fs\Model\Fstab\Fstabline::setContent
     * @covers \Droid\Plugin\Fs\Model\Fstab\Fstabline::parse
     * @param string $originalLine
     * @param string $exceptionMessage
     */
    public function testSetContentWithInvalidOriginalLineThrowsException(
        $originalLine,
        $exceptionMessage
    ) {
        $line = new FstabLine;

        $this->setExpectedException(
            'Droid\Plugin\Fs\Model\Fstab\FstabException',
            $exceptionMessage
        );

        $line->setContent($originalLine);
    }

    public function provideInvalidOriginalLines()
    {
        return array(
            'Insufficient fields' => array(
                '/dev/sda1 /mnt/point1 ext3       ',
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
     * @covers \Droid\Plugin\Fs\Model\Fstab\Fstabline::__toString
     */
    public function testToStringReturnsEmptyString()
    {
        $line = new FstabLine;

        $this->assertSame(
            '',
            (string) $line,
            'An unused line is an empty string.'
        );
    }

    /**
     * @covers \Droid\Plugin\Fs\Model\Fstab\Fstabline::__toString
     */
    public function testToStringWithOnlyGivenValuesReturnsNewLine()
    {
        $expectedValues = array(
            '/dev/sda1',
            '/mnt/point1',
            'ext3',
            'rw',
        );

        $line = new FstabLine;

        $line
            ->setFileSystem($expectedValues[0])
            ->setMountPoint($expectedValues[1])
            ->setFileSystemType($expectedValues[2])
            ->setOptions($expectedValues[3])
        ;

        $this->assertSame(
            implode(' ', $expectedValues),
            (string) $line,
            'A line of only given values is a new line made up of only given values.'
        );
    }

    /**
     * @dataProvider provideValidOriginalLines
     * @covers \Droid\Plugin\Fs\Model\Fstab\Fstabline::setContent
     * @covers \Droid\Plugin\Fs\Model\Fstab\Fstabline::parse
     * @covers \Droid\Plugin\Fs\Model\Fstab\Fstabline::__toString
     * @param string $originalLine
     */
    public function testToStringWithoutMutationReturnsUnchangedOriginalLine($originalLine)
    {
        $line = new FstabLine;

        $this->assertSame(
            $originalLine,
            (string) $line->setContent($originalLine),
            'Original line is returned unchanged.'
        );
    }

    public function provideValidOriginalLines()
    {
        return array(
            'Empty line' => array(''),
            'Comment line' => array('# No comment. Erm, sorry: this is a comment.'),
            'Filesystem line' => array('/dev/sda1 /mnt/point1 ext3 rw'),
            'Complete Filesystem line' => array('/dev/sda1 /mnt/point1 ext3 rw 1 2'),
        );
    }

    /**
     * @dataProvider provideMutatedLines
     * @covers \Droid\Plugin\Fs\Model\Fstab\Fstabline::parse
     * @covers \Droid\Plugin\Fs\Model\Fstab\Fstabline::__toString
     * @param string $originalLine
     * @param string $mutator
     * @param mixed $value
     * @param string $expectedLine
     */
    public function testToStringWithMutationReturnsModifiedOriginalLine(
        $originalLine,
        $mutator,
        $value,
        $expectedLine
    ) {
        $line = new FstabLine;

        $this->assertSame(
            $expectedLine,
            (string) $line->setContent($originalLine)->$mutator($value),
            'Original line is modified.'
        );
    }

    public function provideMutatedLines()
    {
        return array(
            'Non-string fs' => array(
                '/dev/sda1  /mnt/point1 ext3 rw 1 2',
                'setFileSystem',
                '/dev/sda2',
                '/dev/sda2 /mnt/point1 ext3 rw 1 2',
            ),
            'Non-string mount point' => array(
                '/dev/sda1 /mnt/point1 ext3 rw 1 2',
                'setMountPoint',
                '/mnt/point2',
                '/dev/sda1 /mnt/point2 ext3 rw 1 2',
            ),
            'Non-string fs type' => array(
                '/dev/sda1 /mnt/point1 ext3 rw 1 2',
                'setFileSystemType',
                'nfs',
                '/dev/sda1 /mnt/point1 nfs rw 1 2',
            ),
            'Non-string options' => array(
                '/dev/sda1 /mnt/point1 ext3 rw 1 2',
                'setOptions',
                'ro',
                '/dev/sda1 /mnt/point1 ext3 ro 1 2',
            ),
            'Empty string dump' => array(
                '/dev/sda1 /mnt/point1 ext3 rw 1 2',
                'setDump',
                0,
                '/dev/sda1 /mnt/point1 ext3 rw 0 2',
            ),
            'Empty string pass' => array(
                '/dev/sda1 /mnt/point1 ext3 rw 1 2',
                'setPass',
                0,
                '/dev/sda1 /mnt/point1 ext3 rw 1 0',
            ),
        );
    }

    /**
     * @covers \Droid\Plugin\Fs\Model\Fstab\Fstabline::getFileSystem
     */
    public function testGetFileSystemWithoutGivenOrOriginalValuesWillReturnNull()
    {
        $line = new FstabLine;

        $this->assertNull(
            $line->getFileSystem(),
            'getFileSystem returns null without a parsed line or a given value for fileSystem.'
        );
    }

    /**
     * @covers \Droid\Plugin\Fs\Model\Fstab\Fstabline::getFileSystem
     */
    public function testGetFileSystemWithoutGivenValueWillReturnOriginalValue()
    {
        $line = new FstabLine;

        $this->assertSame(
            '/dev/sda1',
            $line->setContent('/dev/sda1 /mnt/point1 ext3 rw')->getFileSystem(),
            'getFileSystem returns the original value when there is no value given for fileSystem.'
        );
    }

    /**
     * @covers \Droid\Plugin\Fs\Model\Fstab\Fstabline::getFileSystem
     */
    public function testGetFileSystemWithGivenValueWillReturnGivenValue()
    {
        $line = new FstabLine;

        $line->setContent('/dev/sda1 /mnt/point1 ext3 rw');

        $this->assertSame(
            '/dev/sda2',
            $line->setFileSystem('/dev/sda2')->getFileSystem(),
            'getFileSystem returns the value given for fileSystem.'
        );
    }

    /**
     * @covers \Droid\Plugin\Fs\Model\Fstab\Fstabline::getMountPoint
     */
    public function testGetMountPointWithoutGivenOrOriginalValuesWillReturnNull()
    {
        $line = new FstabLine;

        $this->assertNull(
            $line->getMountPoint(),
            'getFileSystem returns null without a parsed line or a given value for mountPoint.'
        );
    }

    /**
     * @covers \Droid\Plugin\Fs\Model\Fstab\Fstabline::getMountPoint
     */
    public function testGetMountPointmWithoutGivenValueWillReturnOriginalValue()
    {
        $line = new FstabLine;

        $this->assertSame(
            '/mnt/point1',
            $line->setContent('/dev/sda1 /mnt/point1 ext3 rw')->getMountPoint(),
            'getFileSystem returns the original value when there is no value given for mountPoint.'
        );
    }

    /**
     * @covers \Droid\Plugin\Fs\Model\Fstab\Fstabline::getMountPoint
     */
    public function testGetMountPointmWithGivenValueWillReturnGivenValue()
    {
        $line = new FstabLine;

        $line->setContent('/dev/sda1 /mnt/point1 ext3 rw');

        $this->assertSame(
            '/mnt/point2',
            $line->setMountPoint('/mnt/point2')->getMountPoint(),
            'getFileSystem returns the value given for mountPoint.'
        );
    }
}
