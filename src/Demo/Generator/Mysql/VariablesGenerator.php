<?php

declare(strict_types=1);

namespace App\Demo\Generator\Mysql;

use App\Demo\Generator\DemoMetricGeneratorInterface;
use App\Demo\State\DemoState;
use App\Metrics\Consumer\Consumer;

class VariablesGenerator implements DemoMetricGeneratorInterface
{
    public function getConsumer(): Consumer
    {
        return Consumer::MYSQL_VARIABLES;
    }

    public function generate(DemoState $state): array
    {
        return [
            'character_set_client' => 'utf8mb4',
            'character_set_connection' => 'utf8mb4',
            'character_set_database' => 'utf8mb4',
            'character_set_results' => 'utf8mb4',
            'character_set_server' => 'utf8mb4',
            'character_set_system' => 'utf8mb3',
            'collation_connection' => 'utf8mb4_0900_ai_ci',
            'collation_server' => 'utf8mb4_0900_ai_ci',
            'innodb_buffer_pool_size' => (string) (1024 * 1024 * 1024),
            'join_buffer_size' => '262144',
            'long_query_time' => '2.0',
            'max_connections' => '151',
            'max_heap_table_size' => (string) (16 * 1024 * 1024),
            'slow_query_log' => 'ON',
            'slow_query_log_file' => '/var/log/mysql/slow.log',
            'sort_buffer_size' => '262144',
            'system_time_zone' => 'UTC',
            'table_open_cache' => '4000',
            'thread_cache_size' => '100',
            'time_zone' => '+00:00',
            'timestamp' => (string) time(),
            'tmp_table_size' => (string) (16 * 1024 * 1024),
            'version' => '8.0.39',
            'version_comment' => 'MySQL Community Server - GPL',
            'wait_timeout' => '28800',
            'log_bin' => 'ON',
        ];
    }
}
