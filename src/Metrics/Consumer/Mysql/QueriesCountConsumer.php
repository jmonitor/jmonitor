<?php

declare(strict_types=1);

namespace App\Metrics\Consumer\Mysql;

use App\Metrics\Consumer\Consumer;
use App\Metrics\Consumer\ConsumerInterface;
use App\Metrics\Dto\Bag\Mysql\MysqlQueriesCountBag;
use App\Metrics\Dto\MetricBagDto;
use InfluxDB2\Point;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\Collection;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\Type;

#[AsTaggedItem(Consumer::MYSQL_QUERY_COUNT->value)]
class QueriesCountConsumer implements ConsumerInterface
{
    public function getMetricsToCache(MetricBagDto $bag): array
    {
        return [];
    }

    public function normalizeBag(MetricBagDto $bag): MetricBagDto
    {
        return $bag;
    }

    /**
     * @param MysqlQueriesCountBag $bag
     */
    public function getInfluxPoints(MetricBagDto $bag): array
    {
        $points = [];

        $point = new Point('mysql_queries_count');

        if ($bag->nbDelete >= 0) {
            $point->addField('nb_delete', $bag->nbDelete);
            $ok = true;
        }

        if ($bag->nbInsert >= 0) {
            $point->addField('nb_insert', $bag->nbInsert);
            $ok = true;
        }

        if ($bag->nbSelect >= 0) {
            $point->addField('nb_select', $bag->nbSelect);
            $ok = true;
        }

        if ($bag->nbUpdate >= 0) {
            $point->addField('nb_update', $bag->nbUpdate);
            $ok = true;
        }

        if (($ok ?? false) && $bag->dbName) {
            $point->addTag('db_name', $bag->dbName);
            $points[] = $point;
        }

        return $points;
    }

    public function getConstraints(int $version): Constraint|array
    {
        return new Collection(
            fields: [
                'schema_name' => [new Type('string'), new Length(max: 128)],
                'total_select_queries' => [new Type('int'), new GreaterThanOrEqual(0)],
                'total_insert_queries' => [new Type('int'), new GreaterThanOrEqual(0)],
                'total_update_queries' => [new Type('int'), new GreaterThanOrEqual(0)],
                'total_delete_queries' => [new Type('int'), new GreaterThanOrEqual(0)],
            ],
            allowMissingFields: true,
        );
    }
}
