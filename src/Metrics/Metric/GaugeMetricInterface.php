<?php

declare(strict_types=1);

namespace App\Metrics\Metric;

use App\Metrics\Renderer\Dto\GaugeDto;

interface GaugeMetricInterface extends MetricInterface, TypicalRangeAwareMetricInterface
{
    public function configureGauge(GaugeDto $gauge, array $options = []): void;
}
