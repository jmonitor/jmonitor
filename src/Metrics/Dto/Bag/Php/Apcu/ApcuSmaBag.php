<?php

declare(strict_types=1);

namespace App\Metrics\Dto\Bag\Php\Apcu;

use App\Metrics\Dto\Bag;

class ApcuSmaBag extends Bag
{
    public ?int $numSegments {
        get => $this->getInt('num_seg');
    }

    /**
     * Memory allocated per segment (as set in the configuration)
     */
    public ?int $segmentSize {
        get => $this->getInt('seg_size');
    }

    /**
     * Remaining free memory for APCu
     */
    public ?int $availableMem {
        get => $this->getInt('avail_mem');
    }

    /**
     * Total memory allocated to APCu
     */
    public ?int $totalMem {
        get => $this->segmentSize !== null && $this->numSegments !== null ? $this->segmentSize * $this->numSegments : null;
    }

    public ?int $usedMem {
        get => $this->totalMem !== null && $this->availableMem !== null ? $this->totalMem - $this->availableMem : null;
    }

    public ?float $usedMemPercent {
        get => $this->usedMem !== null && $this->totalMem > 0 ? round(($this->usedMem / $this->totalMem) * 100, 2) : null;
    }
}
