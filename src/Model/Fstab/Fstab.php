<?php

namespace Droid\Plugin\Fs\Model\Fstab;

use RuntimeException;
use SplFileObject;

class Fstab
{
    private $file;
    private $inserts = array();
    private $isParsed;
    private $lineFactory;
    private $lineMap;
    private $path;
    private $updates = array();

    public function __construct(
        FstabLineFactory $lineFactory,
        $path
    ) {
        $this->path = $path;
        $this->lineFactory = $lineFactory;
    }

    /**
     * Queue a new fstab entry or an update to an fstab entry to be later
     * written to an fstab file.
     *
     * Changes pending to the fstab file are committed upon calling "finish".
     * @see Fstab::finish
     *
     * @param string $filesystem
     * @param string $mountPoint
     * @param string $type
     * @param string $options
     * @param integer $dump
     * @param integer $pass
     *
     * @throws \Droid\Plugin\Fs\Model\Fstab\FstabException if the fstab file
     *                                    cannot be read, written and parsed
     *
     * @return \Droid\Plugin\Fs\Model\Fstab\Fstab
     */
    public function addEntry(
        $filesystem,
        $mountPoint,
        $type,
        $options = 'ro',
        $dump = null,
        $pass = null
    ) {
        $this->parse();

        if (isset($this->lineMap[$filesystem][$mountPoint])) {
            list($lineNum, $line) = $this->lineMap[$filesystem][$mountPoint];
            $line
                ->setFileSystemType($type)
                ->setOptions($options)
            ;
            if ($dump !== null) {
                $line->setDump($dump);
            }
            if ($pass !== null) {
                $line->setPass($pass);
            }
            if (! $line->changed()) {
                # forget this line if we had previously remembered it for update
                unset($this->updates[$lineNum]);
                return $this;
            }
            $this->updates[$lineNum] = $line;
            return $this;
        }

        $line = $this->lineFactory->makeLine();
        $line
            ->setFileSystem($filesystem)
            ->setMountPoint($mountPoint)
            ->setFileSystemType($type)
            ->setOptions($options)
            ->setDump($dump !== null ? $dump : 0)
            ->setPass($pass !== null ? $pass : 0)
        ;
        $this->inserts[] = $line;

        return $this;
    }

    /**
     * @return boolean True if the fstab file has changes to be written
     */
    public function changed()
    {
        return $this->inserts || $this->updates;
    }

    /**
     * Call this to write pending changes to the fstab file
     */
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
            throw new FstabException(
                'Expected the fstab file to be a readable file.',
                null,
                $e
            );
        }
        if (! $this->file->isWritable()) {
            throw new FstabException('Expected the fstab file to be writeable.');
        }
    }

    private function parse()
    {
        if ($this->isParsed) {
            return;
        }

        $this->open();

        foreach ($this->file as $lineNum => $lineContent) {
            $line = $this->lineFactory->makeLine();
            try {
                $line->setContent(rtrim($lineContent));
            } catch (FstabException $prev) {
                throw new FstabException(
                    'Expected the fstab file to be well formed.',
                    null,
                    $prev
                );
            }
            if ($line->isParsedFileSystemInfo()) {
                $this->lineMap[$line->getFileSystem()][$line->getMountPoint()] = array($lineNum, $line);
            }
        }
        $this->isParsed = true;
    }
}
