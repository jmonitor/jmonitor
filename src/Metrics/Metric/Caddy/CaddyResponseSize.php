<?php

declare(strict_types=1);

namespace App\Metrics\Metric\Caddy;

use App\Chart\Dto\TimeSeriesChartConfiguration;
use App\Chart\Units\Bytes;
use App\Chart\Units\Unit;
use App\Metrics\BagAwareTrait;
use App\Metrics\Metric;
use App\Metrics\Metric\OptionsAwareMetricInterface;
use App\Metrics\Metric\TimeSeriesMetricInterface;
use App\Metrics\Model\Influx\QueryBuilder;
use App\Metrics\Renderer\Dto\TimeSerieDto;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;
use Symfony\Component\OptionsResolver\OptionsResolver;

#[AsTaggedItem(Metric::CaddyResponseSize->value)]
class CaddyResponseSize implements TimeSeriesMetricInterface, OptionsAwareMetricInterface
{
    use BagAwareTrait;

    public function getMetric(): Metric
    {
        return Metric::CaddyResponseSize;
    }

    public function configureTimeSerie(TimeSerieDto $lineDto, array $options = []): void
    {
        $handler = $options['handler'];

        $currentValue = $this->getCaddyBag()?->responseSizePerSec->getInt($handler) !== null
            ? Bytes::parse($this->getCaddyBag()->responseSizePerSec->getInt($handler))->perSecond()
            : null;

        $lineDto
            ->setCurrentValue($currentValue?->roundFinalValue(2), $currentValue?->getUnit())
            ->setMeasurement('caddy_handler')
            ->setFields(['response_size_bytes_sum' => ''])
            ->setQueryBuilder(function (QueryBuilder $queryBuilder) use ($handler): void {
                $queryBuilder
                    ->tag('handler', $handler)
                    ->derivative(nonNegative: true)
                ;
            })
        ;
    }

    public function configureTimeSerieChart(TimeSeriesChartConfiguration $config): void
    {
        $config
            ->setYMin(0)
            ->setUnit(Unit::Byte)
        ;
    }

    public function configureOptions(OptionsResolver $optionsResolver): void
    {
        $optionsResolver->setRequired('handler');
        $optionsResolver->setAllowedValues('handler', ['php', 'file_server']);
    }
}
