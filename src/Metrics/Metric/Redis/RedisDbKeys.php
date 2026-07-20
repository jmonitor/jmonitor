<?php

declare(strict_types=1);

namespace App\Metrics\Metric\Redis;

use App\Chart\Dto\TimeSeriesChartConfiguration;
use App\Metrics\BagAwareTrait;
use App\Metrics\Metric;
use App\Metrics\Metric\TimeSeriesMetricInterface;
use App\Metrics\Metric\OptionsAwareMetricInterface;
use App\Metrics\Model\Influx\QueryBuilder;
use App\Metrics\Renderer\Dto\TimeSerieDto;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;
use Symfony\Component\OptionsResolver\OptionsResolver;

#[AsTaggedItem(Metric::RedisDbKeys->value)]
class RedisDbKeys implements TimeSeriesMetricInterface, OptionsAwareMetricInterface
{
    use BagAwareTrait;

    public function getMetric(): Metric
    {
        return Metric::RedisDbKeys;
    }

    public function configureTimeSerie(TimeSerieDto $lineDto, array $options = []): void
    {
        $index = $options['db'];

        $db = $this->getRedisBag()->databases[$index] ?? null;

        $lineDto
            ->setValueAvailable((bool) $db)
            ->setCurrentValue($db?->keys, 'keys')
            ->setMeasurement('redis_db')
            ->setFields(['keys' => ''])
            ->setQueryBuilder(function (QueryBuilder $queryBuilder) use ($index): void {
                $queryBuilder->tag('db', $index);
            })
        ;
    }

    public function configureTimeSerieChart(TimeSeriesChartConfiguration $config): void {}

    public function configureOptions(OptionsResolver $optionsResolver): void
    {
        $optionsResolver->setRequired('db');
        $optionsResolver->setAllowedTypes('db', 'int');
    }
}
