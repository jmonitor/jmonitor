<?php

declare(strict_types=1);

namespace App\Metrics\Metric\Caddy;

use App\Chart\Dto\TimeSeriesChartConfiguration;
use App\Metrics\BagAwareTrait;
use App\Metrics\Metric;
use App\Metrics\Metric\OptionsAwareMetricInterface;
use App\Metrics\Metric\TimeSeriesMetricInterface;
use App\Metrics\Model\Influx\QueryBuilder;
use App\Metrics\Renderer\Dto\TimeSerieDto;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;
use Symfony\Component\OptionsResolver\OptionsResolver;

#[AsTaggedItem(Metric::CaddyReqPerSec->value)]
class CaddyReqPerSec implements TimeSeriesMetricInterface, OptionsAwareMetricInterface
{
    use BagAwareTrait;

    public function getMetric(): Metric
    {
        return Metric::CaddyReqPerSec;
    }

    public function configureTimeSerie(TimeSerieDto $lineDto, array $options = []): void
    {
        $handler = $options['handler'];

        $lineDto
            ->setValueAvailable($this->getCaddyBag()?->requestsPerSec->getFloat($handler) !== null)
            ->setCurrentValue($this->getCaddyBag()?->requestsPerSec->getFloat($handler), 'req/s')
            ->setMeasurement('caddy_handler')
            ->setFields(['requests_total' => ''])
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
        ;
    }

    public function configureOptions(OptionsResolver $optionsResolver): void
    {
        $optionsResolver->setRequired('handler');
        $optionsResolver->setAllowedValues('handler', ['php', 'file_server']);
    }
}
