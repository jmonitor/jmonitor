<?php

declare(strict_types=1);

namespace App\Metrics\Dto\Bag\Redis;

use App\Metrics\Dto\Bag;

class ClientsBag extends Bag
{
    public ?int $connected {
        get => $this->getInt('connected');
    }
}
