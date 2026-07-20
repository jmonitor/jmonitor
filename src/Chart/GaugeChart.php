<?php

declare(strict_types=1);

namespace App\Chart;

use Symfony\UX\Chartjs\Model\Chart;

/**
 * NOT USED FOR NOW.
 * A gauge to display a single value, as a percentage.
 *
 * https://www.chartjs.org/docs/latest/charts/doughnut.html
 * https://www.chartjs.org/chartjs-plugin-annotation/master/guide/types/doughnutLabel.html
 * https://www.chartjs.org/chartjs-plugin-annotation/master/samples/doughnutLabel/gauge.html
 */
class GaugeChart implements ChartInterface
{
    public function __construct(private readonly Chart $chart, private readonly string $title, private readonly ?string $subtitle = null) {}

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getSubtitle(): ?string
    {
        return $this->subtitle;
    }

    public function getChart(): ?Chart
    {
        return $this->chart;
    }
}
