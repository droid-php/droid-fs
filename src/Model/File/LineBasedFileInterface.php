<?php

namespace Droid\Plugin\Fs\Model\File;

interface LineBasedFileInterface
{
    /**
     * Append a new line or update an existing one to be later written to the
     * file.
     *
     * Changes pending to the file are committed upon calling "finish".
     * @see LineBasedFile::finish
     *
     * @param \Droid\Plugin\Fs\Model\File\LineInterface $line
     *
     * @throws \Droid\Plugin\Fs\Model\File\UnusableFileException if the file
     *                                    cannot be read, written and parsed
     *
     * @return \Droid\Plugin\Fs\Model\File\LineBasedFileInterface
     */
    public function setLine(LineInterface $line);

    /**
     * @return boolean True if the file has changes to be written
     */
    public function changed();

    /**
     * Make a copy of the file at the path given.
     *
     * @param string $filename Path to the backup file.
     *
     * @return \Droid\Plugin\Fs\Model\File\LineBasedFileInterface
     */
    public function backup($filename);

    /**
     * Call this to write pending changes to the file.
     *
     * @return void
     */
    public function finish();
}
