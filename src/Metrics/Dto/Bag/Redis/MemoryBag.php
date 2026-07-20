<?php

declare(strict_types=1);

namespace App\Metrics\Dto\Bag\Redis;

use App\Metrics\Dto\Bag;

class MemoryBag extends Bag
{
    public ?int $used {
        get => $this->getInt('used');
    }

    public ?int $usedRss {
        get => $this->getInt('used_rss');
    }

    public ?int $usedPeak {
        get => $this->getInt('used_peak');
    }

    public ?int $maxMemory {
        get => $this->getInt('max_memory');
    }

    public ?string $maxMemoryPolicy {
        get => $this->get('max_memory_policy');
    }

    public ?float $usedPercent {
        get {
            if ($this->maxMemory === null || $this->used === null || $this->maxMemory === 0) {
                return null;
            }

            return round($this->used / $this->maxMemory * 100, 2);
        }
    }

    public ?float $fragmentationRatio {
        get {
            if ($this->used <= 0 || $this->usedRss === null) {
                return null;
            }

            return round($this->usedRss / $this->used, 2);
        }
    }
}
