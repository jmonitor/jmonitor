<?php

declare(strict_types=1);

namespace App\Metrics\Dto\Bag\Php\Opcache;

use App\Metrics\Dto\Bag;

class OpcacheBag extends Bag
{
    public private(set) ConfigBag $config {
        get => $this->config ??= new ConfigBag($this->all('config'));
    }

    public private(set) StatusBag $status {
        get => $this->status ??= new StatusBag($this->all('status'));
    }
}
