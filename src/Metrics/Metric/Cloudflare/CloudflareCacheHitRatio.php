<?php

declare(strict_types=1);

namespace App\Metrics\Metric\Cloudflare;

use App\Chart\Dto\TimeSeriesChartConfiguration;
use App\Metrics\Consumer\Cloudflare\CloudflareConsumer;
use App\Metrics\Dto\Bag\Cloudflare\TrafficBag;
use App\Metrics\Metric;
use App\Metrics\Metric\GaugeMetricInterface;
use App\Metrics\Metric\TimeSeriesMetricInterface;
use App\Metrics\Renderer\Dto\GaugeDto;
use App\Metrics\Renderer\Dto\TimeSerieDto;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

#[AsTaggedItem(Metric::CloudflareCacheHitRatio->value)]
class CloudflareCacheHitRatio implements GaugeMetricInterface, TimeSeriesMetricInterface
{
    use CloudflareBlockTrait;

    public function getMetric(): Metric
    {
        return Metric::CloudflareCacheHitRatio;
    }

    public function configureGauge(GaugeDto $gauge, array $options = []): void
    {
        $traffic = $this->block($gauge, 'traffic');

        if (!$traffic instanceof TrafficBag) {
            return;
        }

        $gauge->setValue($this->percent($traffic), 1);
    }

    public function configureTimeSerie(TimeSerieDto $lineDto, array $options = []): void
    {
        $traffic = $this->block($lineDto, 'traffic');

        $lineDto
            ->setCurrentValue($traffic instanceof TrafficBag ? $this->percent($traffic) : null, '%')
            ->setMeasurement(CloudflareConsumer::MEASUREMENT)
            ->setFields(['cache_hit_percent' => 'Cache hit'])
            ->setMinimumWindow(60)
        ;
    }

    public function configureTimeSerieChart(TimeSeriesChartConfiguration $config): void
    {
        $config
            ->setYMin(0)
            ->setYMax(100)
        ;
    }

    public function getTypicalRangeValue(array $options = []): int|float|null
    {
        $traffic = $this->getCloudflareBag()?->traffic;

        return $traffic === null ? null : $this->percent($traffic);
    }

    private function percent(TrafficBag $traffic): ?float
    {
        $ratio = $traffic->window->cacheHitRatio();

        return $ratio === null ? null : round($ratio * 100, 1);
    }
}
