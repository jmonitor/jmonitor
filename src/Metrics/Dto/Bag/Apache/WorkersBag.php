<?php

declare(strict_types=1);

namespace App\Metrics\Dto\Bag\Apache;

use App\Metrics\Dto\Bag;

class WorkersBag extends Bag
{
    public ?int $busy {
        get => $this->get('busy');
    }

    public ?int $idle {
        get => $this->get('idle');
    }

    public ?int $total {
        get => $this->busy >= 0 && $this->idle >= 0 ? $this->busy + $this->idle : null;
    }

    public private(set) ?int $used = null {
        get {
            if ($this->used) {
                return $this->used;
            }

            if ($this->total >= 0 && $this->idle >= 0) {
                return $this->used = $this->total - $this->idle;
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
