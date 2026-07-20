<?php

declare(strict_types=1);

namespace App\Metrics\Dto\Bag\System;

use App\Metrics\Dto\Bag;

class DiskBag extends Bag
{
    public ?int $total {
        get => $this->get('total');
    }

    public ?int $free {
        get => $this->get('free');
    }

    public private(set) ?int $used = null {
        get {
            if ($this->used) {
                return $this->used;
            }

            if ($this->total >= 0 && $this->free >= 0) {
                return $this->used = $this->total - $this->free;
            }

            return null;
        }
    }

    public private(set) ?float $usedPercent = null {
        get {
            if ($this->usedPercent !== null) {
                return $this->usedPercent;
            }

            if ($this->used >= 0 && $this->total > 0) {
                return $this->usedPercent = round(($this->used / $this->total) * 100, 2);
            }

            return null;
        }
    }
}
