<?php

declare(strict_types=1);

namespace App\Metrics\Metric;

use App\Metrics\Metric;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('app.metric')]
interface MetricInterface
{
    public function getMetric(): Metric;
}
