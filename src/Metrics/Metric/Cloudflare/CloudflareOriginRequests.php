<?php

declare(strict_types=1);

namespace App\Metrics\Metric\Cloudflare;

use App\Chart\Dto\TimeSeriesChartConfiguration;
use App\Metrics\Consumer\Cloudflare\CloudflareConsumer;
use App\Metrics\Dto\Bag\Cloudflare\TrafficBag;
use App\Metrics\Metric;
use App\Metrics\Metric\TimeSeriesMetricInterface;
use App\Metrics\Renderer\Dto\TimeSerieDto;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

#[AsTaggedItem(Metric::CloudflareOriginRequests->value)]
class CloudflareOriginRequests implements TimeSeriesMetricInterface
{
    use CloudflareBlockTrait;

    public function getMetric(): Metric
    {
        return Metric::CloudflareOriginRequests;
    }

    public function configureTimeSerie(TimeSerieDto $lineDto, array $options = []): void
    {
        $traffic = $this->block($lineDto, 'traffic');

        $lineDto
            ->setCurrentValue($traffic instanceof TrafficBag ? $traffic->originRequests() : null, 'requests over the last minutes')
            ->setMeasurement(CloudflareConsumer::MEASUREMENT)
            ->setFields(['origin_requests' => 'Origin requests'])
            ->setMinimumWindow(60)
        ;
    }

    public function configureTimeSerieChart(TimeSeriesChartConfiguration $config): void
    {
        $config->setYMin(0);
    }
}
