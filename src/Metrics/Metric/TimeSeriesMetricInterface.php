<?php

declare(strict_types=1);

namespace App\Metrics\Metric;

use App\Chart\Dto\TimeSeriesChartConfiguration;
use App\Metrics\Renderer\Dto\TimeSerieDto;

interface TimeSeriesMetricInterface extends MetricInterface
{
    public function configureTimeSerie(TimeSerieDto $lineDto, array $options = []): void;

    public function configureTimeSerieChart(TimeSeriesChartConfiguration $config): void;
}
