<?php

declare(strict_types=1);

namespace App\Metrics\Metric\Cloudflare;

use App\Chart\Dto\TimeSeriesChartConfiguration;
use App\Chart\Units\Unit;
use App\Metrics\Consumer\Cloudflare\CloudflareConsumer;
use App\Metrics\Dto\Bag\Cloudflare\TrafficBag;
use App\Metrics\Metric;
use App\Metrics\Metric\BasicMetricInterface;
use App\Metrics\Metric\TimeSeriesMetricInterface;
use App\Metrics\Renderer\Dto\BasicDto;
use App\Metrics\Renderer\Dto\TimeSerieDto;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

#[AsTaggedItem(Metric::CloudflareOriginResponseTime->value)]
class CloudflareOriginResponseTime implements BasicMetricInterface, TimeSeriesMetricInterface
{
    use CloudflareBlockTrait;

    public function getMetric(): Metric
    {
        return Metric::CloudflareOriginResponseTime;
    }

    public function configureBasicDto(BasicDto $dto, array $options = []): void
    {
        $traffic = $this->block($dto, 'traffic');

        if ($traffic instanceof TrafficBag) {
            $dto
                ->setValue($traffic)
                ->setContext(['latest' => $traffic->latestOrigin()])
            ;
        }
    }

    public function configureTimeSerie(TimeSerieDto $lineDto, array $options = []): void
    {
        $traffic = $this->block($lineDto, 'traffic');

        $lineDto
            ->setCurrentValue($traffic instanceof TrafficBag ? $traffic->latestOrigin()?->originResponseMsP50 : null, 'ms (P50)')
            ->setMeasurement(CloudflareConsumer::MEASUREMENT)
            ->setFields(['origin_response_ms_p50' => 'P50', 'origin_response_ms_p95' => 'P95'])
            ->setMinimumWindow(60)
        ;
    }

    public function configureTimeSerieChart(TimeSeriesChartConfiguration $config): void
    {
        $config
            ->setYMin(0)
            ->setUnit(Unit::Millisecond)
        ;
    }
}
