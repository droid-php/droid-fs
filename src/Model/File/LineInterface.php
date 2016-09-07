<?php

namespace Droid\Plugin\Fs\Model\File;

interface LineInterface
{
    /**
     * Set the content of a line and parse its constituent fields.
     *
     * @param string $content
     *
     * @return \Droid\Plugin\Fs\Model\File\LineInterface
     */
    public function set($content);

    /**
     * Set the value of a named field.
     *
     * @param string $fieldName
     * @param string $value
     *
     * @return \Droid\Plugin\Fs\Model\File\LineInterface
     */
    public function setFieldValue($fieldName, $value);

    /**
     * Get the value of a named field.
     *
     * @param string $fieldName
     *
     * @return mixed
     */
    public function getFieldValue($fieldName);

    /**
     * Determine whether or not this is a parsed data line.
     *
     * @return boolean
     */
    public function isData();

    /**
     * Determine whether this started as a parsed data line and a change was
     * subsequently made to the value of one or more of its fields.
     *
     * @return boolean
     */
    public function changed();

    /**
     * Copy given values to the supplied line.
     *
     * @param \Droid\Plugin\Fs\Model\File\LineInterface $line
     *
     * @return \Droid\Plugin\Fs\Model\File\LineInterface
     */
    public function update(LineInterface $line);

    /**
     * Format the supplied values as a string.
     *
     * @param array $values Ordered list of values
     *
     * @return string
     */
    public function format($values);

    /**
     * Set the characters which separate fields.
     *
     * @param string $separator
     *
     * @return \Droid\Plugin\Fs\Model\File\LineInterface
     */
    public function setFieldSeparator($separator);

    /**
     * Get a list of values for use as the key in a mapping of values to line.
     *
     * @return array
     */
    public function getMappingValues();

    /**
     * Return the string content of the line.
     *
     * @return string
     */
    public function __toString();
}
