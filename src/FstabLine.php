<?php

namespace Droid\Plugin\Fs;

use RuntimeException;

class FstabLine
{
    protected $content;
    public function getContent()
    {
        return $this->content;
    }

    public function setContent($content)
    {
        $this->content = $content;

        if (trim($content, " \t")=='') {
            $this->type = 'empty';
            return $this;
        }
        if (substr($content, 0, 1)=='#') {
            $this->type = 'comment';
            return $this;
        }

        $this->type = 'mount';


        // Remove trailing tabs
        $line = trim($this->content, "\t");
        $line = str_replace(' ', "\t", $line);
        // Remove multiple tabs
        $line = str_replace("\t\t\t", "\t", $line);
        $line = str_replace("\t\t\t", "\t", $line);
        $line = str_replace("\t\t", "\t", $line);

        // Split into parts
        $part = explode("\t", $line);
        if (count($part)!=6) {
            throw new RuntimeException("Line contains unexpected amount of parts: " . count($part) . ": [" . $line . "]");
        }
        $this->setFileSystem($part[0]);
        $this->setMountPoint($part[1]);
        $this->setFileSystemType($part[2]);
        $this->setOptions($part[3]);
        $this->setDump($part[4]);
        $this->setPass($part[5]);

        return $this;
    }

    protected $type;
    public function getType()
    {
        return $this->type;
    }

    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }


    protected $fileSystem;
    public function getFileSystem()
    {
        return $this->fileSystem;
    }

    public function setFileSystem($fileSystem)
    {
        $this->fileSystem = $fileSystem;
        return $this;
    }

    protected $mountPoint;
    public function getMountPoint()
    {
        return $this->mountPoint;
    }

    public function setMountPoint($mountPoint)
    {
        $this->mountPoint = $mountPoint;
        return $this;
    }

    protected $fileSystemType;

    public function getFileSystemType()
    {
        return $this->fileSystemType;
    }

    public function setFileSystemType($fileSystemType)
    {
        $this->fileSystemType = $fileSystemType;
        return $this;
    }
    protected $options;
    public function getOptions()
    {
        return $this->options;
    }

    public function setOptions($options)
    {
        $this->options = $options;
        return $this;
    }

    protected $dump;
    public function getDump()
    {
        return $this->dump;
    }

    public function setDump($dump)
    {
        $this->dump = $dump;
        return $this;
    }

    protected $pass;
    public function getPass()
    {
        return $this->pass;
    }

    public function setPass($pass)
    {
        $this->pass = $pass;
        return $this;
    }

    public function render()
    {
        switch ($this->getType()) {
            case 'comment':
                // leave untouched
                return $this->content . "\n";
            case 'empty':
                return '';
            case 'mount':
                $o = $this->getFileSystem() . "\t";
                $o .= $this->getMountPoint() . "\t";
                $o .= $this->getFileSystemType() . "\t";
                $o .= $this->getOptions() . "\t";
                $o .= $this->getDump() . "\t";
                $o .= $this->getPass();
                return $o . "\n";
            default:
                throw new RuntimeException("Unsupported line type: " . $this->getType());
                break;
        }
    }
}
