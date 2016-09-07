<?php

namespace Droid\Plugin\Fs\Model\File;

class FileFactory
{
    private $fileClassName;
    private $lineFactory;

    public function __construct(
        $fileClassName,
        LineFactory $lineFactory
    ) {
        $this->fileClassName = $fileClassName;
        $this->lineFactory = $lineFactory;
    }

    /**
     * @return \Droid\Plugin\Fs\Model\File\LineFactory
     */
    public function getLineFactory()
    {
        return $this->lineFactory;
    }

    public function makeFile($filename)
    {
        return new $this->fileClassName($this->lineFactory, $filename);
    }
}
