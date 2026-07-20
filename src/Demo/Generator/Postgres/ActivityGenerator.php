<?php

declare(strict_types=1);

namespace App\Demo\Generator\Postgres;

use App\Demo\Generator\DemoMetricGeneratorInterface;
use App\Demo\State\DemoState;
use App\Metrics\Consumer\Consumer;

class ActivityGenerator implements DemoMetricGeneratorInterface
{
    public function getConsumer(): Consumer
    {
        return Consumer::POSTGRES_ACTIVITY;
    }

    public function generate(DemoState $state): array
    {
        $season = $state->seasonality();
        $active = (int) round($state->walk('pg.active', 1, 8, 0.15) * $season) ?: 1;
        $idle = (int) round($state->walk('pg.idle', 3, 18, 0.1) * $season);
        $idleInTransaction = rand(0, 2);
        $numbackends = $active + $idle + $idleInTransaction;

        $blocked = rand(0, 100) > 90;
        $blockedQueries = [];
        $blockedCount = 0;
        $maxWaitSeconds = null;

        if ($blocked) {
            $waitSeconds = rand(1, 30);
            $blockedCount = 1;
            $maxWaitSeconds = $waitSeconds;
            $blockedQueries[] = [
                'blocked_pid' => rand(10000, 30000),
                'blocked_wait_seconds' => $waitSeconds,
                'blocked_query_sample' => 'UPDATE orders SET status = $1 WHERE id = $2',
                'blocking_pid' => rand(10000, 30000),
                'blocking_query_sample' => 'SELECT * FROM orders WHERE id = $1 FOR UPDATE',
                'blocking_state' => 'idle in transaction',
            ];
        }

        return [
            'database_stats' => [
                'numbackends' => $numbackends,
                'xact_commit' => $state->counter('pg.xact_commit', (int) round(rand(100, 1000) * $season)),
                'xact_rollback' => $state->counter('pg.xact_rollback', rand(0, 100) > 80 ? rand(1, 10) : 0),
                'blks_read' => $state->counter('pg.blks_read', rand(10, 200)),
                'blks_hit' => $state->counter('pg.blks_hit', (int) round(rand(5000, 20000) * $season)),
                'tup_returned' => $state->counter('pg.tup_returned', (int) round(rand(1000, 50000) * $season)),
                'tup_fetched' => $state->counter('pg.tup_fetched', (int) round(rand(500, 20000) * $season)),
                'tup_inserted' => $state->counter('pg.tup_inserted', (int) round(rand(10, 500) * $season)),
                'tup_updated' => $state->counter('pg.tup_updated', (int) round(rand(10, 300) * $season)),
                'tup_deleted' => $state->counter('pg.tup_deleted', rand(0, 50)),
                'conflicts' => $state->counter('pg.conflicts', 0),
                'deadlocks' => $state->counter('pg.deadlocks', rand(0, 100) > 95 ? 1 : 0),
                'temp_files' => $state->counter('pg.temp_files', rand(0, 100) > 90 ? 1 : 0),
                'temp_bytes' => $state->counter('pg.temp_bytes', rand(0, 100) > 90 ? rand(1000000, 50000000) : 0),
            ],
            'bgwriter' => [
                'checkpoints_timed' => $state->counter('pg.cp_timed', rand(0, 1)),
                'checkpoints_req' => $state->counter('pg.cp_req', rand(0, 100) > 90 ? 1 : 0),
                'buffers_checkpoint' => $state->counter('pg.buf_cp', rand(0, 500)),
                'buffers_clean' => $state->counter('pg.buf_clean', rand(0, 200)),
                'maxwritten_clean' => $state->counter('pg.maxwritten', 0),
                'buffers_alloc' => $state->counter('pg.buf_alloc', rand(100, 2000)),
                'buffers_backend' => $state->counter('pg.buf_backend', rand(0, 100)),
            ],
            'connections' => [
                'active' => $active,
                'idle' => $idle,
                'idle in transaction' => $idleInTransaction,
            ],
            'sessions' => [
                'oldest_transaction_seconds' => rand(0, 100) > 70 ? rand(1, 120) : 0,
                'idle_in_transaction_count' => $idleInTransaction,
                'oldest_idle_in_transaction_seconds' => $idleInTransaction > 0 ? rand(1, 60) : 0,
                'blocked_count' => $blockedCount,
                'max_wait_seconds' => $maxWaitSeconds,
                'blocked_queries' => $blockedQueries,
            ],
        ];
    }
}
