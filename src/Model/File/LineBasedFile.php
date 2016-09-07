<?php

namespace Droid\Plugin\Fs\Model\File;

use DomainException;
use RuntimeException;
use SplFileObject;

class LineBasedFile implements LineBasedFileInterface
{
    protected $file;
    protected $inserts = array();
    protected $isParsed;
    protected $lineFactory;
    protected $lineMap;
    protected $path;
    protected $updates = array();

    public function __construct(
        LineFactory $lineFactory,
        $path
    ) {
        $this->lineFactory = $lineFactory;
        $this->path = $path;
    }

    public function setLine(LineInterface $line)
    {
        $this->open();
        $this->parse();

        $ptr = & $this->lineMap;
        foreach ($line->getMappingValues() as $key) {
            if (isset($ptr[$key])) {
                $ptr = & $ptr[$key];
            } else {
                $ptr = null;
            }
        }
        if ($ptr) {
            list($lineNum, $existingLine) = $ptr;
            $line->update($existingLine);
            if (! $existingLine->changed()) {
                # forget this line if we had previously remembered it for update
                unset($this->updates[$lineNum]);
                return $this;
            }
            $this->updates[$lineNum] = $existingLine;
        } else {
            $this->inserts[] = $line;
        }

        return $this;
    }

    public function changed()
    {
        return $this->inserts || $this->updates;
    }

    public function finish()
    {
        if (! $this->changed()) {
            return;
        }

        # read existing lines and truncate
        $this->file->rewind();
        $toWrite = array();
        foreach ($this->file as $line) {
            $toWrite[] = trim($line);
        }
        $this->file->ftruncate(0);
        $this->file->rewind();

        # overwrite existing lines with updates (whose keys are original line nums)
        if (sizeof($this->updates)) {
            $toWrite = array_replace(
                $toWrite,
                array_map(
                    function ($x) {
                        return (string) $x;
                    },
                    $this->updates
                )
            );
        }

        # append inserts
        if (sizeof($this->inserts)) {
            $toWrite = array_merge(
                $toWrite,
                array_map(
                    function ($x) {
                        return (string) $x;
                    },
                    $this->inserts
                ),
                array('')
            );
        }

        $toWrite = implode("\n", $toWrite);
        $this->file->fwrite($toWrite, strlen($toWrite));
        $this->file->fflush();
    }

    public function backup($filename)
    {
        $this->open();
        $this->file->rewind();

        $backup = new SplFileObject($filename, 'wb');
        $backup->fwrite(
            file_get_contents($this->file->getPathname()),
            $this->file->getSize()
        );
        $backup->fflush();

        return $this;
    }

    private function open()
    {
        if ($this->file) {
            return;
        }

        try {
            $this->file = new SplFileObject($this->path, 'r+b');
        } catch (RuntimeException $e) {
            throw new UnusableFileException(
                'Expected the file to be readable.',
                null,
                $e
            );
        }
        if (! $this->file->isWritable()) {
            $e = new UnusableFileException('Expected the file to be writeable.');
            $e->setUnusableFile($this->file);
            throw $e;
        }
    }

    private function parse()
    {
        if ($this->isParsed) {
            return;
        }

        foreach ($this->file as $lineNum => $lineContent) {
            $line = $this->lineFactory->makeLine();
            try {
                $line->set(rtrim($lineContent));
            } catch (DomainException $prev) {
                $e = new UnusableFileException(
                    'Expected the file to be well formed.',
                    null,
                    $prev
                );
                $e->setUnusableFile($this->file);
                throw $e;
            }
            if ($line->isData()) {
                $ptr = & $this->lineMap;
                foreach ($line->getMappingValues() as $key) {
                    if (! isset($ptr[$key])) {
                        $ptr[$key] = array();
                    }
                    $ptr = & $ptr[$key];
                }
                if ($ptr != $this->lineMap) {
                    array_push($ptr, $lineNum);
                    array_push($ptr, $line);
                }
            }
        }
        $this->isParsed = true;
    }
}
