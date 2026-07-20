<?php

declare(strict_types=1);

namespace App\Metrics\Metric;

use App\Metrics\Renderer\Dto\BasicDto;

interface BasicMetricInterface extends MetricInterface
{
    public function configureBasicDto(BasicDto $dto, array $options = []): void;
}
