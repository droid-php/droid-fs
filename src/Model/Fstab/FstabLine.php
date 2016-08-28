<?php

namespace Droid\Plugin\Fs\Model\Fstab;

use InvalidArgumentException;

class FstabLine
{
    private $defaults = array(
        'dump' => 0,
        'pass' => 0,
    );
    private $givenValues = array();
    private $originalLine;
    private $originalValues = array();

    /**
     * Set a string line from an fstab file and parse it into "originalValues".
     *
     * @param string $content
     *
     * @return \Droid\Plugin\Fs\Model\Fstab\FstabLine
     */
    public function setContent($content)
    {
        $this->originalLine = $content;
        $this->parse();
        return $this;
    }

    public function setFileSystem($fileSystem)
    {
        if (! is_string($fileSystem) || empty($fileSystem)) {
            throw new InvalidArgumentException(
                'The "fileSystem" argument is not a non-empty string.'
            );
        }
        $this->setValue('fileSystem', $fileSystem);
        return $this;
    }

    public function getFileSystem()
    {
        if (array_key_exists('fileSystem', $this->givenValues)) {
            return $this->givenValues['fileSystem'];
        } elseif (array_key_exists('fileSystem', $this->originalValues)) {
            return $this->originalValues['fileSystem'];
        }
        return null;
    }

    public function setMountPoint($mountPoint)
    {
        if (! is_string($mountPoint) || empty($mountPoint)) {
            throw new InvalidArgumentException(
                'The "mountPoint" argument is not a non-empty string.'
            );
        }
        $this->setValue('mountPoint', $mountPoint);
        return $this;
    }

    public function getMountPoint()
    {
        if (array_key_exists('mountPoint', $this->givenValues)) {
            return $this->givenValues['mountPoint'];
        } elseif (array_key_exists('mountPoint', $this->originalValues)) {
            return $this->originalValues['mountPoint'];
        }
        return null;
    }

    public function setFileSystemType($fileSystemType)
    {
        if (! is_string($fileSystemType) || empty($fileSystemType)) {
            throw new InvalidArgumentException(
                'The "fileSystemType" argument is not a non-empty string.'
            );
        }
        $this->setValue('fileSystemType', $fileSystemType);
        return $this;
    }

    public function setOptions($options)
    {
        if (! is_string($options) || empty($options)) {
            throw new InvalidArgumentException(
                'The "options" argument is not a non-empty string.'
            );
        }
        $this->setValue('options', $options);
        return $this;
    }

    public function setDump($dump)
    {
        if (! is_numeric($dump)) {
            throw new InvalidArgumentException(
                'The "dump" argument is not a numeric value.'
            );
        }
        $this->setValue('dump', $dump);
        return $this;
    }

    public function setPass($pass)
    {
        if (! is_numeric($pass)) {
            throw new InvalidArgumentException(
                'The "pass" argument is not a numeric value.'
            );
        }
        $this->setValue('pass', $pass);
        return $this;
    }

    public function __toString()
    {
        if ($this->originalValues && $this->givenValues) {
            # an updated line
            return implode(
                ' ',
                array_values(
                    array_replace($this->originalValues, $this->givenValues)
                )
            );
        } elseif ($this->originalValues || $this->originalLine) {
            # an unchanged line
            return $this->originalLine;
        } elseif ($this->givenValues) {
            # a new line
            return implode(' ', array_values($this->givenValues));
        }
        return '';
    }

    /**
     * Determine whether this instance represents an fstab line which was parsed
     * into a set of filesystem info values.
     *
     * @return boolean
     */
    public function isParsedFileSystemInfo()
    {
        return (bool) sizeof($this->originalValues);
    }

    /**
     * Determine whether this instance started as a parsed fstab line and has
     * since had one or more of its values changed.
     *
     * @return boolean
     */
    public function changed()
    {
        if (empty($this->originalValues)) {
            return false;
        }
        return (bool) sizeof($this->givenValues);
    }

    private function parse()
    {
        if (trim($this->originalLine, " \t") == '') {
            return;
        }
        if (substr($this->originalLine, 0, 1) == '#') {
            return;
        }

        // Normalise horizontal whitespace to single tabs
        $line = str_replace(' ', "\t", trim($this->originalLine, " \t"));
        while (true) {
            $prev = $line;
            $line = str_replace("\t\t", "\t", $line);
            if ($line === $prev) {
                break;
            }
        }

        // Split into parts
        $parts = explode("\t", $line);
        if (sizeof($parts) < 4) {
            throw new FstabException(
                sprintf(
                    'Expected a well-formed line (of 4 or more fields), got: "%s".',
                    $this->originalLine
                )
            );
        }

        $this->originalValues['fileSystem'] = $parts[0];
        $this->originalValues['mountPoint'] = $parts[1];
        $this->originalValues['fileSystemType'] = $parts[2];
        $this->originalValues['options'] = $parts[3];

        if (array_key_exists(4, $parts)) {
            if (! is_numeric($parts[4])) {
                throw new FstabException(
                    sprintf(
                        'Expected a well-formed line; "dump" is not numeric: "%s".',
                        $this->originalLine
                    )
                );
            }
            $this->originalValues['dump'] = (int) $parts[4];
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
            $this->originalValues['pass'] = (int) $parts[5];
        }
    }

    private function setValue($name, $value)
    {
        if (isset($this->originalValues[$name])
            && $this->originalValues[$name] === $value
        ) {
            # the given value is the same as the original value; ignore any given value
            unset($this->givenValues[$name]);
        } elseif (isset($this->originalValues[$name])
            && $this->originalValues[$name] !== $value
            && isset($this->defaults[$name])
            && $this->defaults[$name] === $value
        ) {
            # the given value is the same as a default value, but different from the original value; change it
            $this->givenValues[$name] = $value;
        } elseif (isset($this->defaults[$name])
            && $this->defaults[$name] === $value
        ) {
            # the given value is the same as the default value; ignore any given value
            unset($this->givenValues[$name]);
        } else {
            $this->givenValues[$name] = $value;
        }
    }
}
