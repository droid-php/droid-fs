<?php

namespace Droid\Plugin\Fs\Model\File;

class LineFactory
{
    private $lineClassName;
    private $fieldSeparator;

    public function __construct(
        $lineClassName,
        $fieldSeparator = ' '
    ) {
        $this->lineClassName = $lineClassName;
        $this->fieldSeparator = $fieldSeparator;
    }

    public function getFieldSeparator()
    {
        return $this->fieldSeparator;
    }

    public function setFieldSeparator($fieldSeparator)
    {
        $this->fieldSeparator = $fieldSeparator;
    }

    public function makeLine()
    {
        $line = new $this->lineClassName;
        $line->setFieldSeparator($this->fieldSeparator);
        return $line;
    }
}
