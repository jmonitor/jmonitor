<?php

declare(strict_types=1);

namespace App\Demo\Generator;

use App\Demo\State\DemoState;
use App\Metrics\Consumer\Consumer;

class CaddyGenerator implements DemoMetricGeneratorInterface
{
    public function getConsumer(): Consumer
    {
        return Consumer::CADDY;
    }

    public function generate(DemoState $state): array
    {
        $season = $state->seasonality();
        $phpReqs = (int) round(rand(50, 1000) * $season);
        $fileReqs = (int) round(rand(10, 100) * $season);

        return [
            'version' => '2.10.2',
            'requests_total' => [
                'php' => (int) $state->counter('caddy.req_php', $phpReqs),
                'file_server' => (int) $state->counter('caddy.req_file', $fileReqs),
                'static_response' => 0,
            ],
            'requests_in_flight' => [
                'php' => (int) round($state->walk('caddy.flight_php', 0, 10, 0.2) * $season),
                'file_server' => (int) round($state->walk('caddy.flight_file', 0, 5, 0.2) * $season),
                'static_response' => 0,
            ],
            'response_size_bytes_sum' => [
                'php' => (int) $state->counter('caddy.resp_size_php', $phpReqs * rand(2000, 8000)),
                'file_server' => (int) $state->counter('caddy.resp_size_file', $fileReqs * rand(10000, 100000)),
                'static_response' => 0,
            ],
            'response_duration_seconds_sum' => [
                'php' => $state->counter('caddy.resp_dur_php', round($phpReqs * (rand(20, 120) / 1000), 2)),
                'file_server' => $state->counter('caddy.resp_dur_file', round($fileReqs * (rand(1, 20) / 1000), 2)),
                'static_response' => 0,
            ],
            'response_duration_seconds_bucket_le_250ms' => [
                'php' => (int) $state->counter('caddy.bucket_php', (int) round($phpReqs * 0.95)),
                'file_server' => (int) $state->counter('caddy.bucket_file', $fileReqs),
                'static_response' => 0,
            ],
            'request_duration_seconds_sum' => [
                'php' => $state->counter('caddy.req_dur_php', round($phpReqs * (rand(20, 120) / 1000), 2)),
                'file_server' => $state->counter('caddy.req_dur_file', round($fileReqs * (rand(1, 20) / 1000), 2)),
                'static_response' => 0,
            ],
            'request_size_bytes_sum' => [
                'php' => (int) $state->counter('caddy.req_size_php', $phpReqs * rand(500, 3000)),
                'file_server' => (int) $state->counter('caddy.req_size_file', $fileReqs * rand(300, 1500)),
                'static_response' => 0,
            ],
            'process_cpu_seconds_total' => $state->counter('caddy.cpu', round(rand(5, 80) / 100, 2)),
            'process_resident_memory_bytes' => (int) round($state->walk('caddy.mem', 30000000, 120000000, 0.05)),
            'process_start_time_seconds' => time() - 1000000,
        ];
    }
}
