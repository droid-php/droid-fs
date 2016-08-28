<?php

namespace Droid\Plugin\Fs\Model;

use InvalidArgumentException;
use RuntimeException;

use Symfony\Component\Process\ProcessBuilder;
use Symfony\Component\Process\Exception\ProcessFailedException;

class FsMount
{
    private $processBuilder;

    public function __construct(ProcessBuilder $processBuilder)
    {
        $this->processBuilder = $processBuilder;
    }

    public function mounted($mountPoint)
    {
        if (! is_string($mountPoint) || empty($mountPoint)) {
            throw new InvalidArgumentException(
                'Expected a non-empty string mount point.'
            );
        }

        $p = $this->getProcess(array('mountpoint', '-q', $mountPoint));

        return $p->run() === 0;
    }

    public function mount($mountPoint)
    {
        if (! is_string($mountPoint) || empty($mountPoint)) {
            throw new InvalidArgumentException(
                'Expected a non-empty string mount point.'
            );
        }

        $p = $this->getProcess(array('mount', $mountPoint));

        if ($p->run()) {
            throw new RuntimeException(
                sprintf('Failed to mount "%s".', $mountPoint),
                null,
                new ProcessFailedException($p)
            );
        }

        return true;
    }

    public function umount($mountPoint)
    {
        if (! is_string($mountPoint) || empty($mountPoint)) {
            throw new InvalidArgumentException(
                'Expected a non-empty string mount point.'
            );
        }

        $p = $this->getProcess(array('umount', $mountPoint));

        if ($p->run()) {
            throw new RuntimeException(
                sprintf('Failed to umount "%s".', $mountPoint),
                null,
                new ProcessFailedException($p)
            );
        }

        return true;
    }

    private function getProcess($arguments)
    {
        return $this
            ->processBuilder
            ->setArguments($arguments)
            ->getProcess()
        ;
    }
}
