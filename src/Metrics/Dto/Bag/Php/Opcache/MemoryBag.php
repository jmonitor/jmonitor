<?php

declare(strict_types=1);

namespace App\Metrics\Dto\Bag\Php\Opcache;

use App\Metrics\Dto\Bag;

class MemoryBag extends Bag
{
    public ?int $used {
        get => $this->get('used_memory');
    }

    public ?int $free {
        get => $this->get('free_memory');
    }

    public ?int $wasted {
        get => $this->get('wasted_memory');
    }

    public ?float $wastedPercent {
        get => $this->getFloat('current_wasted_percentage');
    }

    public ?int $total {
        get => $this->used >= 0 && $this->free >= 0 && $this->wasted >= 0 ? $this->used + $this->free + $this->wasted : null;
    }

    public private(set) ?float $usedPercent = null {
        get {
            if ($this->usedPercent !== null) {
                return $this->usedPercent;
            }

            return $this->usedPercent = $this->total > 0 ? round(($this->used / $this->total) * 100, 2) : null;
        }
    }
}
