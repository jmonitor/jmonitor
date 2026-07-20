<?php

declare(strict_types=1);

namespace App\Metrics\Consumer\Mysql;

use App\Metrics\Consumer\Consumer;
use App\Metrics\Consumer\ConsumerInterface;
use App\Metrics\Dto\Bag\Mysql\MysqlSlowQueriesBag;
use App\Metrics\Dto\Bag\Mysql\SlowQueriesOrderBy;
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

#[AsTaggedItem(Consumer::MYSQL_SLOW_QUERIES->value)]
class SlowQueriesConsumer implements ConsumerInterface
{
    public function normalizeBag(MetricBagDto $bag): MetricBagDto
    {
        return $bag;
    }

    /**
     * @param MysqlSlowQueriesBag $bag
     */
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
        return new Collection(
            fields: [
                'schema_name' => [new Type('string'), new Length(max: 64)],
                'performance_schema_readable' => new Type('bool'), // @deprecated, no longer sent by current collectors, kept while older ones are still deployed
                'min_exec_count' => [new Type('numeric'), new GreaterThanOrEqual(0)],
                'min_avg_time_ms' => [new Type('numeric'), new GreaterThanOrEqual(0)],
                'limit' => [new Type('numeric'), new Range(min: 1, max: 10)],
                'order_by' => [new Type('string'), new Choice(choices: SlowQueriesOrderBy::formChoices())],
                'slow_queries' => [
                    new Type('array'),
                    new All(constraints: [
                        new Collection(
                            fields: [
                                'query_sample' => [new Type('string'), new Length(max: 500)],
                                'exec_count' => [new Type('numeric'), new GreaterThanOrEqual(0)],
                                'total_time_ms' => [new Type('numeric'), new GreaterThanOrEqual(0)],
                                'avg_time_ms' => [new Type('numeric'), new GreaterThanOrEqual(0)],
                                'max_time_ms' => [new Type('numeric'), new GreaterThanOrEqual(0)],
                            ],
                            allowMissingFields: true,
                        ),
                    ]),
                ],
            ],
            allowExtraFields: false,
            allowMissingFields: true,
        );
    }
}
