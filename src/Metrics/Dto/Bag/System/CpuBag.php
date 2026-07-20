<?php

declare(strict_types=1);

namespace App\Metrics\Dto\Bag\System;

use App\Metrics\Dto\Bag;

class CpuBag extends Bag
{
    public ?int $nbCores {
        get => $this->get('cores');
    }

    public ?int $usedPercent {
        get => $this->get('load');
    }

    public ?float $load1 {
        get => $this->get('load1') !== null ? round($this->get('load1'), 2) : null;
    }

    public ?float $load5 {
        get => $this->get('load5') !== null ? round($this->get('load5'), 2) : null;
    }

    public ?float $load15 {
        get => $this->get('load15') !== null ? round($this->get('load15'), 2) : null;
    }
}
