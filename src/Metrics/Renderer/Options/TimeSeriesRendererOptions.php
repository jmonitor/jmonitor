<?php

declare(strict_types=1);

namespace App\Metrics\Renderer\Options;

use App\Chart\Dto\TimeSeriesChartConfiguration;
use App\Chart\TimeRange;

class TimeSeriesRendererOptions implements RendererOptionsInterface
{
    public private(set) TimeSeriesChartConfiguration $chartConfig;

    public function __construct()
    {
        $this->chartConfig = new TimeSeriesChartConfiguration();
    }

    public function setRange(?TimeRange $range): static
    {
        $this->chartConfig->setRange($range);

        return $this;
    }
}
