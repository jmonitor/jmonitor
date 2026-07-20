<?php

declare(strict_types=1);

namespace App\Demo\Generator\Mysql;

use App\Demo\Generator\DemoMetricGeneratorInterface;
use App\Demo\State\DemoState;
use App\Metrics\Consumer\Consumer;

class StatusGenerator implements DemoMetricGeneratorInterface
{
    public function getConsumer(): Consumer
    {
        return Consumer::MYSQL_STATUS;
    }

    public function generate(DemoState $state): array
    {
        $season = $state->seasonality();
        $poolPages = 65536;
        $poolFree = (int) round($state->walk('mysql.pool_free', 8000, 40000, 0.03));

        return [
            'Uptime' => (string) (3600 * 24 * 9),
            'Threads_connected' => (string) (int) round($state->walk('mysql.threads_conn', 5, 80, 0.08) * $season),
            'Threads_running' => (string) (int) round($state->walk('mysql.threads_run', 1, 20, 0.1) * $season),
            'Threads_created' => (string) $state->counter('mysql.threads_created', rand(0, 2)),
            'Connections' => (string) $state->counter('mysql.connections', (int) round(rand(10, 200) * $season)),
            'Questions' => (string) $state->counter('mysql.questions', (int) round(rand(500, 20000) * $season)),
            'Aborted_connects' => (string) $state->counter('mysql.aborted_connects', rand(0, 100) > 90 ? 1 : 0),
            'Aborted_clients' => (string) $state->counter('mysql.aborted_clients', rand(0, 100) > 92 ? 1 : 0),
            'Created_tmp_tables' => (string) $state->counter('mysql.tmp_tables', (int) round(rand(5, 200) * $season)),
            'Created_tmp_disk_tables' => (string) $state->counter('mysql.tmp_disk', rand(0, 10)),
            'Com_select' => (string) $state->counter('mysql.com_select', (int) round(rand(300, 12000) * $season)),
            'Com_insert' => (string) $state->counter('mysql.com_insert', (int) round(rand(50, 3000) * $season)),
            'Com_update' => (string) $state->counter('mysql.com_update', (int) round(rand(50, 4000) * $season)),
            'Com_delete' => (string) $state->counter('mysql.com_delete', (int) round(rand(10, 800) * $season)),
            'Max_used_connections' => '88',
            'Slow_queries' => (string) $state->counter('mysql.slow', rand(0, 100) > 85 ? 1 : 0),
            'Innodb_buffer_pool_bytes_data' => (string) (($poolPages - $poolFree) * 16384),
            'Innodb_buffer_pool_bytes_free' => (string) ($poolFree * 16384),
            'Innodb_buffer_pool_read_requests' => (string) $state->counter('mysql.pool_read_req', (int) round(rand(5000, 200000) * $season)),
            'Innodb_buffer_pool_reads' => (string) $state->counter('mysql.pool_reads', rand(0, 200)),
            'Innodb_buffer_pool_pages_total' => (string) $poolPages,
            'Innodb_buffer_pool_pages_free' => (string) $poolFree,
            'Innodb_page_size' => '16384',
            'Innodb_data_reads' => (string) $state->counter('mysql.data_reads', rand(10, 2000)),
            'Innodb_data_writes' => (string) $state->counter('mysql.data_writes', rand(10, 4000)),
            'Innodb_data_read' => (string) $state->counter('mysql.data_read', rand(100000, 5000000)),
            'Innodb_data_written' => (string) $state->counter('mysql.data_written', rand(100000, 8000000)),
            'Table_locks_immediate' => (string) $state->counter('mysql.locks_imm', (int) round(rand(100, 5000) * $season)),
            'Table_locks_waited' => (string) $state->counter('mysql.locks_waited', rand(0, 100) > 88 ? 1 : 0),
        ];
    }
}
