<?php

declare(strict_types=1);

namespace App\Metrics\Metric\FrankenPhp;

use App\Chart\Dto\TimeSeriesChartConfiguration;
use App\Chart\Units\Unit;
use App\Metrics\BagAwareTrait;
use App\Metrics\Metric;
use App\Metrics\Metric\OptionsAwareMetricInterface;
use App\Metrics\Metric\TimeSeriesMetricInterface;
use App\Metrics\Model\Influx\QueryBuilder;
use App\Metrics\Renderer\Dto\TimeSerieDto;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;
use Symfony\Component\OptionsResolver\OptionsResolver;

#[AsTaggedItem(Metric::FrankenPhpWorkerPhpExecTime->value)]
class FrankenPhpWorkerPhpExecTime implements TimeSeriesMetricInterface, OptionsAwareMetricInterface
{
    use BagAwareTrait;

    public function getMetric(): Metric
    {
        return Metric::FrankenPhpWorkerPhpExecTime;
    }

    public function configureTimeSerie(TimeSerieDto $lineDto, array $options = []): void
    {
        $index = $options['worker'];
        $worker = $this->getFrankenPhpBag()->workers[$index] ?? null;

        $lineDto
            ->setValueAvailable((bool) $worker)
            ->setCurrentValue($worker->realWorkerRequestTimeAvgMs, 'ms')
            ->setMeasurement('frankenphp_worker')
            ->setFields(['real_request_time_avg_ms' => ''])
            ->setQueryBuilder(function (QueryBuilder $queryBuilder) use ($index): void {
                $queryBuilder->tag('worker', $index);
            })
        ;
    }

    public function configureTimeSerieChart(TimeSeriesChartConfiguration $config): void
    {
        $config
            ->setYMin(0)
            ->setUnit(Unit::Millisecond)
        ;
    }

    public function configureOptions(OptionsResolver $optionsResolver): void
    {
        $optionsResolver->setRequired('worker');
        $optionsResolver->setAllowedTypes('worker', 'int');
    }
}
