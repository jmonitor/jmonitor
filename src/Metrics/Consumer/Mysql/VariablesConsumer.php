<?php

declare(strict_types=1);

namespace App\Metrics\Consumer\Mysql;

use App\Metrics\Consumer\Consumer;
use App\Metrics\Consumer\ConsumerInterface;
use App\Metrics\Dto\MetricBagDto;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\Collection;

#[AsTaggedItem(Consumer::MYSQL_VARIABLES->value)]
class VariablesConsumer implements ConsumerInterface
{
    public function getMetricsToCache(MetricBagDto $bag): array
    {
        return $bag->all();
    }

    public function normalizeBag(MetricBagDto $bag): MetricBagDto
    {
        return $bag;
    }

    public function getInfluxPoints(MetricBagDto $bag): array
    {
        return [];
    }

    public function getConstraints(int $version): Constraint|array
    {
        return new Collection(
            fields: [
                'character_set_client' => [new Assert\Type('string'), new Assert\Length(max: 64)],
                'character_set_connection' => [new Assert\Type('string'), new Assert\Length(max: 64)],
                'character_set_database' => [new Assert\Type('string'), new Assert\Length(max: 64)],
                'character_set_results' => [new Assert\Type('string'), new Assert\Length(max: 64)],
                'character_set_server' => [new Assert\Type('string'), new Assert\Length(max: 64)],
                'character_set_system' => [new Assert\Type('string'), new Assert\Length(max: 64)],
                'collation_connection' => [new Assert\Type('string'), new Assert\Length(max: 64)],
                'collation_server' => [new Assert\Type('string'), new Assert\Length(max: 64)],
                'innodb_buffer_pool_size' => [new Assert\Type('numeric'), new Assert\GreaterThanOrEqual(0)],
                'join_buffer_size' => [new Assert\Type('numeric'), new Assert\GreaterThanOrEqual(0)],
                'long_query_time' => [new Assert\Type('numeric'), new Assert\GreaterThanOrEqual(0)],
                'max_connections' => [new Assert\Type('numeric'), new Assert\GreaterThanOrEqual(1)],
                'max_heap_table_size' => [new Assert\Type('numeric'), new Assert\GreaterThanOrEqual(0)],
                'slow_query_log' => new Assert\Choice(choices: ['OFF', 'off', 'ON', 'on', '0', '1']),
                'slow_query_log_file' => [new Assert\Type('string'), new Assert\Length(max: 255)],
                'sort_buffer_size' => [new Assert\Type('numeric'), new Assert\GreaterThanOrEqual(0)],
                'system_time_zone' => [new Assert\Type('string'), new Assert\Length(max: 64)],
                'table_open_cache' => [new Assert\Type('numeric'), new Assert\GreaterThanOrEqual(0)],
                'thread_cache_size' => [new Assert\Type('numeric'), new Assert\GreaterThanOrEqual(0)],
                'time_zone' => [new Assert\Type('string'), new Assert\Length(max: 64)],
                'timestamp' => [new Assert\Type('numeric'), new Assert\GreaterThanOrEqual(0)],
                'tmp_table_size' => [new Assert\Type('numeric'), new Assert\GreaterThanOrEqual(0)],
                'version' => [new Assert\Type('string'), new Assert\Length(max: 32)],
                'version_comment' => [new Assert\Type('string'), new Assert\Length(max: 128)],
                'wait_timeout' => [new Assert\Type('numeric'), new Assert\GreaterThanOrEqual(0)],
                'log_bin' => [new Assert\Type('string'), new Assert\Length(max: 255)],
            ],
            allowExtraFields: true,
            allowMissingFields: true,
        );
    }
}
