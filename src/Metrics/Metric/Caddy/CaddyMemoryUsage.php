<?php

declare(strict_types=1);

namespace App\Metrics\Metric\Caddy;

use App\Chart\Dto\TimeSeriesChartConfiguration;
use App\Chart\Units\Bytes;
use App\Chart\Units\Unit;
use App\Metrics\BagAwareTrait;
use App\Metrics\Metric;
use App\Metrics\Metric\TimeSeriesMetricInterface;
use App\Metrics\Renderer\Dto\TimeSerieDto;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

#[AsTaggedItem(Metric::CaddyMemoryUsage->value)]
class CaddyMemoryUsage implements TimeSeriesMetricInterface
{
    use BagAwareTrait;

    public function getMetric(): Metric
    {
        return Metric::CaddyMemoryUsage;
    }

    public function configureTimeSerie(TimeSerieDto $lineDto, array $options = []): void
    {
        $currentValue = $this->getCaddyBag()?->processResidentMemoryBytes !== null
            ? Bytes::parse($this->getCaddyBag()->processResidentMemoryBytes)
            : null;

        $lineDto
            ->setCurrentValue($currentValue?->roundFinalValue(2), $currentValue?->getUnit())
            ->setMeasurement('caddy')
            ->setFields(['process_resident_memory_bytes' => ''])
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
