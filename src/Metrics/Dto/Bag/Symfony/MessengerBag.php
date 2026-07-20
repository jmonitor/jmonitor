<?php

declare(strict_types=1);

namespace App\Metrics\Dto\Bag\Symfony;

use App\Metrics\Dto\Bag;

class MessengerBag extends Bag
{
    public array $transports {
        get => $this->all('transports');
    }

    public array $uncountableTransports {
        get => $this->all('uncountable_transports');
    }

    public function getTransport(string $name): array
    {
        return $this->transports[$name] ?? [];
    }
}
