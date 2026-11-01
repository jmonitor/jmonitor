<?php

declare(strict_types=1);

namespace App\Metrics\Metric\Cloudflare;

use App\Chart\Dto\TimeSeriesChartConfiguration;
use App\Chart\Units\Unit;
use App\Metrics\Consumer\Cloudflare\CloudflareConsumer;
use App\Metrics\Dto\Bag\Cloudflare\TrafficBag;
use App\Metrics\Dto\Bag\Cloudflare\TrafficMinute;
use App\Metrics\Metric;
use App\Metrics\Metric\TimeSeriesMetricInterface;
use App\Metrics\Renderer\Dto\TimeSerieDto;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

#[AsTaggedItem(Metric::CloudflareBandwidth->value)]
class CloudflareBandwidth implements TimeSeriesMetricInterface
{
    use CloudflareBlockTrait;

    public function getMetric(): Metric
    {
        return Metric::CloudflareBandwidth;
    }

    public function configureTimeSerie(TimeSerieDto $lineDto, array $options = []): void
    {
        $traffic = $this->block($lineDto, 'traffic');

        $lineDto
            ->setCurrentValue($traffic instanceof TrafficBag ? array_sum(array_map(fn(TrafficMinute $m): int => $m->bytes, $traffic->minutes)) : null, $traffic instanceof TrafficBag ? sprintf('B over the last %d min', $traffic->spanMinutes) : null)
            ->setMeasurement(CloudflareConsumer::MEASUREMENT)
            ->setFields(['bytes' => 'Edge', 'cached_bytes' => 'From cache'])
            ->setMinimumWindow(60)
        ;
    }

    public function configureTimeSerieChart(TimeSeriesChartConfiguration $config): void
    {
        $config
            ->setYMin(0)
            ->setUnit(Unit::Byte)
        ;
    }
}
