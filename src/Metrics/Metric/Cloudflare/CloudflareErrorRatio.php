<?php

declare(strict_types=1);

namespace App\Metrics\Metric\Cloudflare;

use App\Chart\ChartColor;
use App\Chart\Dto\TimeSeriesChartConfiguration;
use App\Metrics\Consumer\Cloudflare\CloudflareConsumer;
use App\Metrics\Metric;
use App\Metrics\Metric\TimeSeriesMetricInterface;
use App\Metrics\Renderer\Dto\TimeSerieDto;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

#[AsTaggedItem(Metric::CloudflareErrorRatio->value)]
class CloudflareErrorRatio implements TimeSeriesMetricInterface
{
    use CloudflareBlockTrait;

    public function getMetric(): Metric
    {
        return Metric::CloudflareErrorRatio;
    }

    public function configureTimeSerie(TimeSerieDto $lineDto, array $options = []): void
    {
        $this->block($lineDto, 'traffic');

        $lineDto
            ->setMeasurement(CloudflareConsumer::MEASUREMENT)
            ->setFields(['status_4xx_percent' => '4xx', 'status_5xx_percent' => '5xx'])
            ->setMinimumWindow(60)
        ;
    }

    public function configureTimeSerieChart(TimeSeriesChartConfiguration $config): void
    {
        $config
            ->setYMin(0)
            ->setColor('status_4xx_percent', ChartColor::ORANGE)
            ->setColor('status_5xx_percent', ChartColor::RED)
        ;
    }
}
