<?php

declare(strict_types=1);

namespace App\Metrics\Dto\Bag\Php\Opcache;

use App\Metrics\Dto\Bag;

class JitBag extends Bag
{
    public ?bool $enabled {
        get => $this->get('enabled');
    }

    public ?bool $on {
        get => $this->get('on');
    }

    public ?int $kind {
        get => $this->get('kind');
    }

    public ?int $optLevel {
        get => $this->get('opt_level');
    }

    public ?int $optFlags {
        get => $this->get('opt_flags');
    }

    public ?int $bufferSize {
        get => $this->get('buffer_size');
    }

    public ?int $bufferFree {
        get => $this->get('buffer_free');
    }
}
