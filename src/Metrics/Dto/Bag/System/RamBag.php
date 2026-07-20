<?php

declare(strict_types=1);

namespace App\Metrics\Dto\Bag\System;

use App\Metrics\Dto\Bag;

class RamBag extends Bag
{
    public ?int $total {
        get => $this->get('total');
    }

    public ?int $available {
        get => $this->get('available');
    }

    public private(set) ?int $used {
        get => $this->used ??= $this->total > 0 && $this->available >= 0 ? $this->total - $this->available : null;
    }

    public private(set) ?float $usedPercent {
        get => $this->usedPercent ??= $this->used >= 0 && $this->total > 0 ? round($this->used / $this->total * 100, 2) : null;
    }
}
