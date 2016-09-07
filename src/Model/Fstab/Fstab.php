<?php

namespace Droid\Plugin\Fs\Model\Fstab;

use Droid\Plugin\Fs\Model\File\LineBasedFile;
use Droid\Plugin\Fs\Model\File\UnusableFileException;

class Fstab extends LineBasedFile
{
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
        $line = $this->lineFactory->makeLine();

        $line
            ->setFileSystem($filesystem)
            ->setMountPoint($mountPoint)
            ->setFileSystemType($type)
            ->setOptions($options)
            ->setDump($dump !== null ? $dump : 0)
            ->setPass($pass !== null ? $pass : 0)
        ;

        try {
            $this->setLine($line);
        } catch (UnusableFileException $e) {
            throw new FstabException(
                'Unable to add an entry to the fstab file because it is unusable.',
                null,
                $e
            );
        }

        return $this;
    }
}
