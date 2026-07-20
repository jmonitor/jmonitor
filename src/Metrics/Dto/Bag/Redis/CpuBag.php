<?php

declare(strict_types=1);

namespace App\Metrics\Dto\Bag\Redis;

use App\Metrics\Dto\Bag;

class CpuBag extends Bag
{
    public ?float $usedSys { get => $this->getFloat('used_sys'); }
    public ?float $usedUser { get => $this->getFloat('used_user'); }

}
