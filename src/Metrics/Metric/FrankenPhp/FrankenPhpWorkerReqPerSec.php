<?php

declare(strict_types=1);

namespace App\Metrics\Metric\FrankenPhp;

use App\Chart\Dto\TimeSeriesChartConfiguration;
use App\Metrics\BagAwareTrait;
use App\Metrics\Metric;
use App\Metrics\Metric\OptionsAwareMetricInterface;
use App\Metrics\Metric\TimeSeriesMetricInterface;
use App\Metrics\Model\Influx\QueryBuilder;
use App\Metrics\Renderer\Dto\TimeSerieDto;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;
use Symfony\Component\OptionsResolver\OptionsResolver;

#[AsTaggedItem(Metric::FrankenPhpWorkerRequestPerSec->value)]
class FrankenPhpWorkerReqPerSec implements TimeSeriesMetricInterface, OptionsAwareMetricInterface
{
    use BagAwareTrait;

    public function getMetric(): Metric
    {
        return Metric::FrankenPhpWorkerRequestPerSec;
    }

    public function configureTimeSerie(TimeSerieDto $lineDto, array $options = []): void
    {
        $index = $options['worker'];
        $worker = $this->getFrankenPhpBag()->workers[$index] ?? null;

        $lineDto
            ->setValueAvailable((bool) $worker)
            ->setCurrentValue($worker?->workerReqPerSec, 'req/s')
            ->setMeasurement('frankenphp_worker')
            ->setFields(['worker_request_count' => ''])
            ->setQueryBuilder(function (QueryBuilder $queryBuilder) use ($index): void {
                $queryBuilder->derivative(nonNegative: true);
                $queryBuilder->tag('worker', $index);
            })
        ;
    }

    public function configureTimeSerieChart(TimeSeriesChartConfiguration $config): void
    {
        $config
            ->setYMin(0)
        ;
    }

    public function configureOptions(OptionsResolver $optionsResolver): void
    {
        $optionsResolver->setRequired('worker');
        $optionsResolver->setAllowedTypes('worker', 'int');
    }
}
