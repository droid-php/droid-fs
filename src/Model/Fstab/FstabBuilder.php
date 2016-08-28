<?php

namespace Droid\Plugin\Fs\Model\Fstab;


class FstabBuilder
{
    private $lineFactory;

    public function __construct(FstabLineFactory $lineFactory)
    {
        $this->lineFactory = $lineFactory;
    }

    public function buildFstab($fstabFilename)
    {
        return new Fstab($this->lineFactory, $fstabFilename);
    }
}
