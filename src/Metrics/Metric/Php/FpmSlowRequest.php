<?php

declare(strict_types=1);

namespace App\Metrics\Metric\Php;

use App\Chart\Dto\TimeSeriesChartConfiguration;
use App\Metrics\Consumer\Consumer;
use App\Metrics\Dto\Bag\Php\PhpBag;
use App\Metrics\Dto\MetricBagDto;
use App\Metrics\Metric;
use App\Metrics\Metric\ConsumerValueMetricInterface;
use App\Metrics\Metric\TimeSeriesMetricInterface;
use App\Metrics\Renderer\Dto\TimeSerieDto;
use App\Metrics\Renderer\Dto\ConsumerValueDto;
use App\Metrics\Renderer\Model\Badge\Badge;
use App\Metrics\Renderer\Model\Badge\BadgeStyle;
use App\Metrics\Renderer\Options\TimeSeriesRendererOptions;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

#[AsTaggedItem(Metric::PhpFpmSlowRequest->value)]
class FpmSlowRequest implements ConsumerValueMetricInterface, TimeSeriesMetricInterface
{
    public function getMetric(): Metric
    {
        return Metric::PhpFpmSlowRequest;
    }

    public function getConsumer(): Consumer
    {
        return Consumer::PHP;
    }

    /**
     * @param PhpBag $bag
     */
    public function getValue(MetricBagDto $bag): ?int
    {
        return $bag->fpm->slowRequests;
    }

    public function configureValueDto(ConsumerValueDto $dto): void
    {
        $value = $dto->value;

        $badge = match (true) {
            $value === 0 => new Badge(BadgeStyle::SUCCESS, 'None'),
            $value > 0 => new Badge(BadgeStyle::DANGER, 'Detected'),
            default => null,
        };

        $dto->setBadge($badge);
    }

    public function configureTimeSerie(TimeSerieDto $lineDto, array $options = []): void
    {
        $lineDto
            ->setMeasurement('php')
            ->setFields(['slow_requests' => 'Slow requests'])
            ->setQueryBuilder(function ($queryBuilder): void {
                $queryBuilder->derivative(nonNegative: true);
            })
        ;
    }

    public function configureTimeSerieChart(TimeSeriesChartConfiguration $config): void
    {
        $config->setYMin(0);
    }
}
