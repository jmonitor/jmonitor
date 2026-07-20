<?php

declare(strict_types=1);

namespace App\Demo\Generator;

use App\Demo\State\DemoState;
use App\Metrics\Consumer\Consumer;

class RedisGenerator implements DemoMetricGeneratorInterface
{
    public function getConsumer(): Consumer
    {
        return Consumer::REDIS;
    }

    public function generate(DemoState $state): array
    {
        $season = $state->seasonality();
        $maxMemory = 536870912; // 512 MiB
        $used = (int) round($state->walk('redis.mem_used', 10000000, 400000000, 0.04));

        return [
            'server' => [
                'version' => '7.4.0',
                'mode' => 'standalone',
                'port' => 6379,
                'uptime' => 3600 * 24 * 21,
            ],
            'clients' => [
                'connected' => (int) round($state->walk('redis.clients', 5, 120, 0.06) * $season),
            ],
            'memory' => [
                'used' => $used,
                'used_rss' => (int) round($used * 1.2),
                'used_peak' => (int) round($used * 1.4),
                'max_memory' => $maxMemory,
                'max_memory_policy' => 'allkeys-lru',
            ],
            'persistence' => [
                'rdb_bgsave_in_progress' => 0,
                'rdb_last_save_time' => time() - (int) round($state->walk('redis.rdb_save_age', 60, 900, 0.1)),
                'rdb_changes_since_last_save' => (int) $state->counter('redis.rdb_changes', rand(0, 200)),
                'rdb_last_bgsave_status' => 'ok',
                'rdb_last_bgsave_time' => (int) round($state->walk('redis.rdb_bgsave_time', 100, 3000, 0.1)),
                'aof_enabled' => false,
                'aof_rewrite_in_progress' => 0,
                'aof_last_rewrite_time_sec' => -1,
                'aof_last_bgrewrite_status' => 'ok',
                'aof_last_cow_size' => 0,
                'aof_current_size' => 0,
                'aof_rewrite_base_size' => 0,
            ],
            'stats' => [
                'total_connections_received' => (int) $state->counter('redis.conns', (int) round(rand(5, 300) * $season)),
                'total_commands_processed' => (int) $state->counter('redis.cmds', (int) round(rand(500, 50000) * $season)),
                'instantaneous_ops_per_sec' => (int) round($state->walk('redis.ops', 10, 5000, 0.1) * $season),
                'rejected_connections' => 0,
                'expired_keys' => (int) $state->counter('redis.expired', rand(0, 500)),
                'evicted_keys' => (int) $state->counter('redis.evicted', 0),
                'evicted_clients' => 0,
                'keyspace_hits' => (int) $state->counter('redis.hits', (int) round(rand(1000, 90000) * $season)),
                'keyspace_misses' => (int) $state->counter('redis.misses', (int) round(rand(10, 4000) * $season)),
                'tracking_total_keys' => 0,
                'total_error_replies' => (int) $state->counter('redis.errors', rand(0, 100) > 95 ? 1 : 0),
                'total_reads_processed' => (int) $state->counter('redis.reads', rand(500, 60000)),
                'total_writes_processed' => (int) $state->counter('redis.writes', rand(500, 60000)),
                'acl_access_denied_auth' => 0,
            ],
            'replication' => [
                'role' => 'master',
                'connected_slaves' => 0,
            ],
            'cpu' => [
                'used_sys' => round($state->walk('redis.cpu_sys', 0.5, 8.0, 0.05), 2),
                'used_user' => round($state->walk('redis.cpu_user', 0.5, 12.0, 0.05), 2),
            ],
            'databases' => [
                ['keys' => rand(5000, 50000), 'expires' => rand(1000, 10000), 'avg_ttl' => rand(60000, 7200000)],
            ],
            'config' => [
                'save' => '900 1 300 10 60 10000',
            ],
        ];
    }
}
