<?php

declare(strict_types=1);

namespace App\Metrics\Consumer\Mysql;

use App\Metrics\Consumer\Consumer;
use App\Metrics\Consumer\ConsumerInterface;
use App\Metrics\Dto\Bag\Mysql\MysqlInfoSchemaBag;
use App\Metrics\Dto\MetricBagDto;
use InfluxDB2\Point;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\Collection;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\Type;

#[AsTaggedItem(Consumer::MYSQL_INFO_SCHEMA->value)]
class MysqlInformationSchemaConsumer implements ConsumerInterface
{
    public function getMetricsToCache(MetricBagDto $bag): array
    {
        return $bag->all();
    }

    public function normalizeBag(MetricBagDto $bag): MetricBagDto
    {
        return $bag;
    }

    /**
     * @param MysqlInfoSchemaBag $bag
     */
    public function getInfluxPoints(MetricBagDto $bag): array
    {
        $fields = [
            'data_length' => $bag->dataWeight['data_length'],
            'index_length' => $bag->dataWeight['index_length'],
            'total_length' => $bag->dataWeight['total_length'],
        ];

        $fields = array_filter($fields, fn($v): bool => $v !== null);

        if (!$fields) {
            return [];
        }

        $point = new Point('mysql_info_schema');

        foreach ($fields as $key => $value) {
            $point->addField($key, $value);
        }

        return [$point];
    }

    public function getConstraints(int $version): Constraint|array
    {
        return new Collection(
            fields: [
                'schema_name' => [new Type('string'), new Length(max: 64)],
                'information_schema_readable' => new Type('bool'),
                'data_weight' => new Collection(
                    fields: [
                        'data_length' => [new Type('int'), new GreaterThanOrEqual(0)],
                        'index_length' => [new Type('int'), new GreaterThanOrEqual(0)],
                    ],
                    allowExtraFields: false,
                    allowMissingFields: true,
                ),
            ],
            allowExtraFields: false,
            allowMissingFields: true,
        );
    }
}
