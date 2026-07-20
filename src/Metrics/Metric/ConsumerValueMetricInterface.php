<?php

declare(strict_types=1);

namespace App\Metrics\Metric;

use App\Metrics\Consumer\Consumer;
use App\Metrics\Dto\MetricBagDto;
use App\Metrics\Renderer\Dto\ConsumerValueDto;

interface ConsumerValueMetricInterface extends MetricInterface
{
    public function getConsumer(): Consumer;
    public function getValue(MetricBagDto $bag): mixed;
    public function configureValueDto(ConsumerValueDto $dto): void;
}
