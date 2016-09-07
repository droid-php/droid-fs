<?php

namespace Droid\Plugin\Fs\Model\Fstab;

use InvalidArgumentException;

use Droid\Plugin\Fs\Model\File\AbstractLine;

class FstabLine extends AbstractLine
{
    private $defaults = array(
        'dump' => 0,
        'pass' => 0,
    );

    public function getMappingValues()
    {
        return array(
            $this->getFieldValue('fileSystem'),
            $this->getFieldValue('mountPoint'),
        );
    }

    public function setFileSystem($fileSystem)
    {
        if (! is_string($fileSystem) || empty($fileSystem)) {
            throw new InvalidArgumentException(
                'The "fileSystem" argument is not a non-empty string.'
            );
        }
        $this->setFieldValue('fileSystem', $fileSystem);
        return $this;
    }

    public function setMountPoint($mountPoint)
    {
        if (! is_string($mountPoint) || empty($mountPoint)) {
            throw new InvalidArgumentException(
                'The "mountPoint" argument is not a non-empty string.'
            );
        }
        $this->setFieldValue('mountPoint', $mountPoint);
        return $this;
    }

    public function setFileSystemType($fileSystemType)
    {
        if (! is_string($fileSystemType) || empty($fileSystemType)) {
            throw new InvalidArgumentException(
                'The "fileSystemType" argument is not a non-empty string.'
            );
        }
        $this->setFieldValue('fileSystemType', $fileSystemType);
        return $this;
    }

    public function setOptions($options)
    {
        if (! is_string($options) || empty($options)) {
            throw new InvalidArgumentException(
                'The "options" argument is not a non-empty string.'
            );
        }
        $this->setFieldValue('options', $options);
        return $this;
    }

    public function setDump($dump)
    {
        if (! is_numeric($dump)) {
            throw new InvalidArgumentException(
                'The "dump" argument is not a numeric value.'
            );
        }
        $this->setFieldValue('dump', $dump);
        return $this;
    }

    public function setPass($pass)
    {
        if (! is_numeric($pass)) {
            throw new InvalidArgumentException(
                'The "pass" argument is not a numeric value.'
            );
        }
        $this->setFieldValue('pass', $pass);
        return $this;
    }

    /**
     * Set the value of a named field.
     *
     * This implementation allows for fields which have an implied value when
     * not given an explicit value. Thus,
     * @see \Droid\Plugin\Fs\Model\File\AbstractLine::setFieldValue()
     */
    public function setFieldValue($fieldName, $value)
    {
        if (isset($this->originalValues[$fieldName])
            && $this->originalValues[$fieldName] === $value
        ) {
            # the given value is the same as the original value;
            # ignore any given value
            unset($this->givenValues[$fieldName]);
        } elseif (isset($this->originalValues[$fieldName])
            && $this->originalValues[$fieldName] !== $value
            && isset($this->defaults[$fieldName])
            && $this->defaults[$fieldName] === $value
        ) {
            # the given value is the same as a default value, but different from
            # the original value; change it
            $this->givenValues[$fieldName] = $value;
        } elseif (isset($this->defaults[$fieldName])
            && $this->defaults[$fieldName] === $value
        ) {
            # the given value is the same as the default value;
            # ignore any given value
            unset($this->givenValues[$fieldName]);
        } else {
            $this->givenValues[$fieldName] = $value;
        }
        return $this;
    }

    protected function parse($data)
    {
        $parsed = array();

        // Split into parts
        $parts = explode("\t", $this->normaliseWhitespace($data));
        if (sizeof($parts) < 4) {
            throw new FstabException(
                sprintf(
                    'Expected a well-formed line (of 4 or more fields), got: "%s".',
                    $this->originalLine
                )
            );
        }

        $parsed['fileSystem'] = $parts[0];
        $parsed['mountPoint'] = $parts[1];
        $parsed['fileSystemType'] = $parts[2];
        $parsed['options'] = $parts[3];

        if (array_key_exists(4, $parts)) {
            if (! is_numeric($parts[4])) {
                throw new FstabException(
                    sprintf(
                        'Expected a well-formed line; "dump" is not numeric: "%s".',
                        $this->originalLine
                    )
                );
            }
            $parsed['dump'] = (int) $parts[4];
        }
        if (array_key_exists(5, $parts)) {
            if (! is_numeric($parts[5])) {
                throw new FstabException(
                    sprintf(
                        'Expected a well-formed line; "pass" is not numeric: "%s".',
                        $this->originalLine
                    )
                );
            }
            $parsed['pass'] = (int) $parts[5];
        }

        return $parsed;
    }

    protected function normaliseWhitespace($data)
    {
        $data = str_replace(' ', "\t", trim($data, " \t"));
        while (true) {
            $prev = $data;
            $data = str_replace("\t\t", "\t", $data);
            if ($data === $prev) {
                break;
            }
        }
        return $data;
    }
}
