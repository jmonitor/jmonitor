<?php

declare(strict_types=1);

namespace App\Metrics\Dto\Bag\Apache;

use App\Metrics\Dto\Bag;

class ScoreboardBag extends Bag
{
    public int $underscore {
        get => $this->get('_') ?? 0;
    }

    public int $W {
        get => $this->get('W') ?? 0;
    }

    public int $S {
        get => $this->get('S') ?? 0;
    }

    public int $R {
        get => $this->get('R') ?? 0;
    }

    public int $K {
        get => $this->get('K') ?? 0;
    }

    public int $D {
        get => $this->get('D') ?? 0;
    }

    public int $C {
        get => $this->get('C') ?? 0;
    }

    public int $L {
        get => $this->get('L') ?? 0;
    }

    public int $G {
        get => $this->get('G') ?? 0;
    }

    public int $I {
        get => $this->get('I') ?? 0;
    }

    public int $dot {
        get => $this->get('.') ?? 0;
    }

    public ?int $nonBusy {
        get => $this->underscore + $this->dot;
    }

    public private(set) ?int $used = null {
        get {
            if ($this->used) {
                return $this->used;
            }

            return $this->used = $this->total >= 0 ? $this->total - ($this->nonBusy ?? 0) : null;
        }
    }

    public private(set) ?int $total = null {
        get {
            if ($this->total) {
                return $this->total;
            }

            return $this->total = array_sum($this->all());
        }
    }

    public private(set) ?float $usedPercent = null {
        get {
            if ($this->usedPercent !== null) {
                return $this->usedPercent;
            }

            return $this->usedPercent = $this->total > 0 && $this->used >= 0 ? round(($this->used / $this->total) * 100, 2) : null;
        }
    }
}
