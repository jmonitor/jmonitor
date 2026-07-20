<?php

declare(strict_types=1);

namespace App\Demo\Generator\Postgres;

use App\Demo\Generator\DemoMetricGeneratorInterface;
use App\Demo\State\DemoState;
use App\Metrics\Consumer\Consumer;

class DatabaseGenerator implements DemoMetricGeneratorInterface
{
    public function getConsumer(): Consumer
    {
        return Consumer::POSTGRES_DATABASE;
    }

    public function generate(DemoState $state): array
    {
        $totalSize = 50000000 + $state->counter('pg.total_size_growth', rand(0, 80000));
        $indexesSize = (int) round($totalSize * 0.3);
        $dbSize = $totalSize + 30000000;

        $liveTuples = (int) round($state->walk('pg.live_tuples', 50000, 500000, 0.02));
        $deadTuples = rand(0, (int) round($liveTuples * 0.08));

        return [
            'schema' => 'public',
            'db_size' => $dbSize,
            'tables' => [
                'table_count' => 42,
                'live_tuples' => $liveTuples,
                'dead_tuples' => $deadTuples,
                'seq_scans' => $state->counter('pg.seq_scans', rand(0, 50)),
                'idx_scans' => $state->counter('pg.idx_scans', (int) round(rand(500, 5000) * $state->seasonality())),
                'total_size' => $totalSize,
                'indexes_size' => $indexesSize,
            ],
        ];
    }
}
