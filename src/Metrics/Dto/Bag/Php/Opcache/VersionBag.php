<?php

declare(strict_types=1);

namespace App\Metrics\Dto\Bag\Php\Opcache;

use App\Metrics\Dto\Bag;

class VersionBag extends Bag
{
    public ?string $version {
        get => $this->get('version');
    }

    public ?string $name {
        get => $this->get('opcache_product_name');
    }
}
