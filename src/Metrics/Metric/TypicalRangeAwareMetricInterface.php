<?php

declare(strict_types=1);

namespace App\Metrics\Metric;

interface TypicalRangeAwareMetricInterface
{
    public function getTypicalRangeValue(array $options = []): int|float|null;
}
