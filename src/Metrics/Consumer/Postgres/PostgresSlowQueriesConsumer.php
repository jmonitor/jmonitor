<?php

declare(strict_types=1);

namespace App\Metrics\Consumer\Postgres;

use App\Metrics\Consumer\Consumer;
use App\Metrics\Consumer\ConsumerInterface;
use App\Metrics\Dto\MetricBagDto;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\All;
use Symfony\Component\Validator\Constraints\Choice;
use Symfony\Component\Validator\Constraints\Collection;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\Range;
use Symfony\Component\Validator\Constraints\Type;

#[AsTaggedItem(Consumer::POSTGRES_SLOW_QUERIES->value)]
class PostgresSlowQueriesConsumer implements ConsumerInterface
{
    public function normalizeBag(MetricBagDto $bag): MetricBagDto
    {
        return $bag;
    }

    public function getMetricsToCache(MetricBagDto $bag): array
    {
        return $bag->all();
    }

    public function getInfluxPoints(MetricBagDto $bag): array
    {
        return [];
    }

    public function getConstraints(int $version): Constraint|array
    {
        $numericPositive = [new Type('numeric'), new GreaterThanOrEqual(0)];

        return new Collection(
            fields: [
                'min_calls'       => $numericPositive,
                'min_avg_time_ms' => $numericPositive,
                'limit'           => [new Type('numeric'), new Range(min: 1, max: 50)],
                'order_by'        => [new Type('string'), new Choice(choices: ['avg', 'total', 'max'])],
                'slow_queries'    => [
                    new Type('array'),
                    new All(constraints: [
                        new Collection(
                            fields: [
                                'query_sample'     => [new Type('string'), new Length(max: 500)],
                                'exec_count'       => $numericPositive,
                                'total_time_ms'    => $numericPositive,
                                'avg_time_ms'      => $numericPositive,
                                'max_time_ms'      => $numericPositive,
                                'stddev_time_ms'   => $numericPositive,
                                'rows'             => $numericPositive,
                                'shared_blks_hit'  => $numericPositive,
                                'shared_blks_read' => $numericPositive,
                            ],
                            allowExtraFields: true,
                            allowMissingFields: true,
                        ),
                    ]),
                ],
            ],
            allowExtraFields: true,
            allowMissingFields: true,
        );
    }
}
