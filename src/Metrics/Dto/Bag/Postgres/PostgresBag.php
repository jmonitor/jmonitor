<?php

declare(strict_types=1);

namespace App\Metrics\Dto\Bag\Postgres;

use App\Metrics\Dto\MetricBagDto;

class PostgresBag extends MetricBagDto
{
    public ?string $postgresVersion {
        get => $this->get('version');
    }
}
