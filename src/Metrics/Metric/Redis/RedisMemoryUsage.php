<?php

declare(strict_types=1);

namespace App\Metrics\Metric\Redis;

use App\Chart\Dto\TimeSeriesChartConfiguration;
use App\Chart\Units\Bytes;
use App\Chart\Units\Unit;
use App\Metrics\BagAwareTrait;
use App\Metrics\Metric;
use App\Metrics\Metric\GaugeMetricInterface;
use App\Metrics\Metric\TimeSeriesMetricInterface;
use App\Metrics\Metric\BasicMetricInterface;
use App\Metrics\Renderer\Dto\BasicDto;
use App\Metrics\Renderer\Dto\GaugeDto;
use App\Metrics\Renderer\Dto\TimeSerieDto;
use App\Metrics\Renderer\Options\TimeSeriesRendererOptions;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

#[AsTaggedItem(Metric::RedisMemoryUsage->value)]
class RedisMemoryUsage implements GaugeMetricInterface, TimeSeriesMetricInterface, BasicMetricInterface
{
    use BagAwareTrait;

    public function getMetric(): Metric
    {
        return Metric::RedisMemoryUsage;
    }

    public function configureGauge(GaugeDto $gauge, array $options = []): void
    {
        $gauge
            ->setValue($this->getRedisBag()?->memory->usedPercent, 1)
        ;
    }

    public function configureTimeSerie(TimeSerieDto $lineDto, array $options = []): void
    {
        $currentValue = $this->getRedisBag()?->memory->used !== null
            ? Bytes::parse($this->getRedisBag()->memory->used)
            : null;

        $lineDto
            ->setCurrentValue($currentValue?->roundFinalValue(2), $currentValue?->getUnit())
            ->setMeasurement('redis')
            ->setFields(['used_memory' => ''])
        ;
    }

    public function configureBasicDto(BasicDto $dto, array $options = []): void
    {
        $dto->setFormattedValue(
            $this->getRedisBag()?->memory->used
            ? ((string) \Zenstruck\Bytes::parse($this->getRedisBag()->memory->used)->asBinary()) : null,
        );
    }

    public function configureTimeSerieChart(TimeSeriesChartConfiguration $config): void
    {
        $config
            ->setYMin(0)
            ->setUnit(Unit::Byte)
        ;
    }

    public function getTypicalRangeValue(array $options = []): int|float|null
    {
        return $this->getRedisBag()?->memory->usedPercent;
    }
}
