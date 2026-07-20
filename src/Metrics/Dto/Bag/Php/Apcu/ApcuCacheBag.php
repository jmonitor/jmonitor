<?php

declare(strict_types=1);

namespace App\Metrics\Dto\Bag\Php\Apcu;

use App\Metrics\Dto\Bag;

class ApcuCacheBag extends Bag
{
    public ?int $numHits {
        get => $this->getInt('num_hits');
    }

    public ?int $numMisses {
        get => $this->getInt('num_misses');
    }

    public ?float $hitRate {
        get => $this->numHits !== null && $this->numMisses > 0 ? round($this->numHits / ($this->numHits + $this->numMisses) * 100, 2) : null;
    }

    public ?int $numEntries {
        get => $this->getInt('num_entries');
    }

    public ?string $memoryType {
        get => $this->get('memory_type');
    }
}
