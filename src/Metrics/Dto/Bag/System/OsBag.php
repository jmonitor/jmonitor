<?php

declare(strict_types=1);

namespace App\Metrics\Dto\Bag\System;

use App\Metrics\Dto\Bag;

class OsBag extends Bag
{
    public ?string $name {
        get => $this->get('pretty_name');
    }

    public ?int $uptime {
        get => $this->get('uptime');
    }
}
