<?php

namespace Droid\Plugin\Fs\Model\File;

abstract class AbstractLine implements LineInterface
{
    protected $fieldSeparator = ' ';
    protected $givenValues = array();
    protected $originalLine;
    protected $originalValues = array();

    public function set($content)
    {
        $this->originalLine = $content;

        if (trim($this->originalLine, " \t") == ''
            || substr($this->originalLine, 0, 1) == '#'
        ) {
            return $this;
        }

        $this->originalValues = $this->parse($this->originalLine);

        return $this;
    }

    public function setFieldValue($fieldName, $value)
    {
        if (isset($this->originalValues[$fieldName])
            && $this->originalValues[$fieldName] === $value
        ) {
            # the given value is the same as the original value; ignore any given value
            unset($this->givenValues[$fieldName]);
        } else {
            $this->givenValues[$fieldName] = $value;
        }
        return $this;
    }

    public function getFieldValue($fieldName)
    {
        if (array_key_exists($fieldName, $this->givenValues)) {
            return $this->givenValues[$fieldName];
        }
        if (array_key_exists($fieldName, $this->originalValues)) {
            return $this->originalValues[$fieldName];
        }
        return null;
    }

    public function isData()
    {
        return (bool) sizeof($this->originalValues);
    }

    public function changed()
    {
        if (empty($this->originalValues)) {
            return false;
        }
        return (bool) sizeof($this->givenValues);
    }

    public function update(LineInterface $line)
    {
        foreach ($this->givenValues as $fieldName => $value) {
            $line->setFieldValue($fieldName, $value);
        }
        return $this;
    }

    public function format($values)
    {
        return implode($this->fieldSeparator, $values);
    }

    public function setFieldSeparator($separator)
    {
        $this->fieldSeparator = $separator;

        return $this;
    }

    abstract public function getMappingValues();

    public function __toString()
    {
        if ($this->originalValues && $this->givenValues) {
            # an updated line
            return $this->format(
                array_values(
                    array_replace($this->originalValues, $this->givenValues)
                )
            );
        } elseif ($this->originalValues || $this->originalLine) {
            # an unchanged line
            return $this->originalLine;
        } elseif ($this->givenValues) {
            # a new line
            return $this->format(array_values($this->givenValues));
        }
        return '';
    }

    /**
     * Return a mapping of named fields to parsed values.
     *
     * @param string $data
     *
     * @return array
     */
    abstract protected function parse($data);
}
