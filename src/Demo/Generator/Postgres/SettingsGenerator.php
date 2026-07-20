<?php

declare(strict_types=1);

namespace App\Demo\Generator\Postgres;

use App\Demo\Generator\DemoMetricGeneratorInterface;
use App\Demo\State\DemoState;
use App\Metrics\Consumer\Consumer;

class SettingsGenerator implements DemoMetricGeneratorInterface
{
    public function getConsumer(): Consumer
    {
        return Consumer::POSTGRES_SETTINGS;
    }

    public function generate(DemoState $state): array
    {
        return [
            'server_version' => '16.4 (Debian 16.4-1.pgdg120+1)',
            'max_connections' => 100,
            'shared_buffers' => 128 * 1024 * 1024,
            'effective_cache_size' => 4 * 1024 * 1024 * 1024,
            'work_mem' => 4 * 1024 * 1024,
            'maintenance_work_mem' => 64 * 1024 * 1024,
            'wal_level' => 'replica',
            'max_wal_size' => 1024 * 1024 * 1024,
            'checkpoint_completion_target' => '0.9',
            'random_page_cost' => '4',
            'effective_io_concurrency' => '1',
            'log_min_duration_statement' => '-1',
            'TimeZone' => 'UTC',
            'autovacuum' => 'on',
            'autovacuum_vacuum_scale_factor' => '0.2',
            'track_counts' => 'on',
        ];
    }
}
