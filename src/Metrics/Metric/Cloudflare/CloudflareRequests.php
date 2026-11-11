<?php

declare(strict_types=1);

namespace App\Metrics\Metric\Cloudflare;

use App\Chart\ChartColor;
use App\Chart\Dto\TimeSeriesChartConfiguration;
use App\Metrics\Consumer\Cloudflare\CloudflareConsumer;
use App\Metrics\Dto\Bag\Cloudflare\TrafficBag;
use App\Metrics\Metric;
use App\Metrics\Metric\BasicMetricInterface;
use App\Metrics\Metric\TimeSeriesMetricInterface;
use App\Metrics\Renderer\Dto\BasicDto;
use App\Metrics\Renderer\Dto\TimeSerieDto;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

#[AsTaggedItem(Metric::CloudflareRequests->value)]
class CloudflareRequests implements BasicMetricInterface, TimeSeriesMetricInterface
{
    use CloudflareBlockTrait;

    public function getMetric(): Metric
    {
        return Metric::CloudflareRequests;
    }

    public function configureBasicDto(BasicDto $dto, array $options = []): void
    {
        $dto->setValue($this->block($dto, 'traffic'));
    }

    public function configureTimeSerie(TimeSerieDto $lineDto, array $options = []): void
    {
        $traffic = $this->block($lineDto, 'traffic');

        $lineDto
            ->setCurrentValue($traffic instanceof TrafficBag ? $traffic->window->requests : null, $traffic instanceof TrafficBag ? sprintf('requests over %d min', $traffic->window->spanMinutes) : null)
            ->setMeasurement(CloudflareConsumer::MEASUREMENT)
            ->setFields(['requests' => 'Total', 'origin_requests' => 'Reaching origin'])
            ->setMinimumWindow(60)
        ;
    }

    public function configureTimeSerieChart(TimeSeriesChartConfiguration $config): void
    {
        $config
            ->setYMin(0)
            ->setColor('requests', ChartColor::BLUE)
            ->setColor('origin_requests', ChartColor::ORANGE)
        ;
    }
}
