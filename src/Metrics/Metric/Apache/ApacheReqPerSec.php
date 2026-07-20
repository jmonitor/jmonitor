<?php

declare(strict_types=1);

namespace App\Metrics\Metric\Apache;

use App\Chart\Dto\TimeSeriesChartConfiguration;
use App\Metrics\Consumer\Consumer;
use App\Metrics\Dto\Bag\Apache\ApacheBag;
use App\Metrics\Metric;
use App\Metrics\Metric\TimeSeriesMetricInterface;
use App\Metrics\MetricsBagProvider;
use App\Metrics\Model\Influx\QueryBuilder;
use App\Metrics\Renderer\Dto\TimeSerieDto;
use App\Metrics\Renderer\Options\TimeSeriesRendererOptions;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

#[AsTaggedItem(Metric::ApacheReqPerSec->value)]
class ApacheReqPerSec implements TimeSeriesMetricInterface
{
    private MetricsBagProvider $bagProvider;

    public function __construct(MetricsBagProvider $bagProvider)
    {
        $this->bagProvider = $bagProvider;
    }

    public function getMetric(): Metric
    {
        return Metric::ApacheReqPerSec;
    }

    public function configureTimeSerie(TimeSerieDto $lineDto, array $options = []): void
    {
        $bag = $this->bagProvider->getLastBag(Consumer::APACHE, ApacheBag::class);

        $lineDto
            ->setCurrentValue($bag->realRequestsPerSecond, 'req/s')
            ->setMeasurement('apache')
            ->setFields(['total_accesses' => ''])
            ->setQueryBuilder(function (QueryBuilder $queryBuilder): void {
                $queryBuilder->derivative(nonNegative: true);
            })
        ;
    }

    public function configureTimeSerieChart(TimeSeriesChartConfiguration $config): void
    {
        $config->setYMin(0);
    }
}
