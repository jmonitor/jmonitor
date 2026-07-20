<?php

declare(strict_types=1);

namespace App\Demo\Generator;

use App\Demo\State\DemoState;
use App\Metrics\Consumer\Consumer;

class FrankenPhpGenerator implements DemoMetricGeneratorInterface
{
    public function getConsumer(): Consumer
    {
        return Consumer::FRANKENPHP;
    }

    public function generate(DemoState $state): array
    {
        $season = $state->seasonality();
        $totalThreads = 32;
        $busy = (int) round($state->walk('franken.busy', 0, $totalThreads, 0.1) * $season);

        return [
            'version' => '1.9.1',
            'mode' => 'worker',
            'busy_threads' => $busy,
            'total_threads' => $totalThreads,
            'queue_depth' => (int) round($state->walk('franken.queue', 0, 50, 0.15) * $season),
            'workers' => [
                [
                    'name' => 'm#/app/public/index.php',
                    'total_workers' => 10,
                    'busy_workers' => (int) round($state->walk('franken.busy_workers', 0, 10, 0.15) * $season),
                    'worker_request_time' => round($state->walk('franken.req_time', 1, 200, 0.1), 2),
                    'worker_request_count' => (int) $state->counter('franken.req_count', (int) round(rand(20, 600) * $season)),
                    'ready_workers' => (int) round($state->walk('franken.ready_workers', 1, 10, 0.15)),
                    'worker_restarts' => (int) $state->counter('franken.restarts', rand(0, 100) > 97 ? 1 : 0),
                ],
            ],
        ];
    }
}
