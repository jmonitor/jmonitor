<?php

declare(strict_types=1);

namespace App\Metrics\Dto\Bag\Redis;

use App\Metrics\Dto\Bag;

class ReplicationBag extends Bag
{
    public ?string $role { get => $this->get('role'); }
    public ?int $connectedSlaves { get => $this->getInt('connected_slaves'); }
}
