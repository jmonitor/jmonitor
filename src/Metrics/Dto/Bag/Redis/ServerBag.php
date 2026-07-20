<?php

declare(strict_types=1);

namespace App\Metrics\Dto\Bag\Redis;

use App\Metrics\Dto\Bag;

class ServerBag extends Bag
{
    public ?string $version {
        get => $this->get('version');
    }

    public ?string $mode {
        get => $this->get('mode');
    }

    public ?int $port {
        get => $this->getInt('port');
    }

    public ?int $uptime {
        get => $this->getInt('uptime');
    }
}
