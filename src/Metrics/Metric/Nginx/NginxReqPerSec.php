<?php

declare(strict_types=1);

namespace App\Metrics\Metric\Nginx;

use App\Chart\Dto\TimeSeriesChartConfiguration;
use App\Metrics\BagAwareTrait;
use App\Metrics\Metric;
use App\Metrics\Metric\TimeSeriesMetricInterface;
use App\Metrics\Model\Influx\QueryBuilder;
use App\Metrics\Renderer\Dto\TimeSerieDto;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

#[AsTaggedItem(Metric::NginxReqPerSec->value)]
class NginxReqPerSec implements TimeSeriesMetricInterface
{
    use BagAwareTrait;

    public function getMetric(): Metric
    {
        return Metric::NginxReqPerSec;
    }

    public function configureTimeSerie(TimeSerieDto $lineDto, array $options = []): void
    {
        $lineDto
            ->setCurrentValue($this->getNginxBag()?->status->requestsPerSecond, 'req/s')
            ->setMeasurement('nginx')
            ->setFields(['requests' => ''])
            ->setQueryBuilder(function (QueryBuilder $queryBuilder): void {
                $queryBuilder->derivative(nonNegative: true);
            })
        ;
    }

    public function configureTimeSerieChart(TimeSeriesChartConfiguration $config): void {}
}
