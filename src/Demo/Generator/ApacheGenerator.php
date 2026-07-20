<?php

declare(strict_types=1);

namespace App\Demo\Generator;

use App\Demo\State\DemoState;
use App\Metrics\Consumer\Consumer;

class ApacheGenerator implements DemoMetricGeneratorInterface
{
    public function getConsumer(): Consumer
    {
        return Consumer::APACHE;
    }

    public function generate(DemoState $state): array
    {
        $season = $state->seasonality();
        $busy = (int) round($state->walk('apache.busy', 1, 50, 0.1) * $season);
        $idle = max(1, 64 - $busy);
        $load1 = round($state->walk('apache.load1', 0.1, 3.0, 0.1) * $season, 2);

        return [
            'server_version' => 'Apache/2.4.62 (Debian)',
            'server_mpm' => 'prefork',
            'total_accesses' => (int) $state->counter('apache.accesses', (int) round(rand(50, 1000) * $season)),
            'total_bytes' => (int) $state->counter('apache.bytes', (int) round(rand(500, 20000) * $season)),
            'uptime' => 3600 * 24 * 12,
            'scoreboard' => [
                '_' => $idle,                            // waiting for connection
                'S' => 0,                                // starting up
                'R' => (int) round($busy * 0.2),         // reading request
                'W' => (int) round($busy * 0.7),         // sending reply
                'K' => (int) round($busy * 0.1),         // keepalive
                'D' => 0,                                // dns lookup
                'C' => 0,                                // closing connection
                'L' => 0,                                // logging
                'G' => 0,                                // gracefully finishing
                'I' => 0,                                // idle cleanup
                '.' => max(0, 64 - $busy - $idle),       // open slot
            ],
            'workers' => [
                'busy' => $busy,
                'idle' => $idle,
            ],
            'load1' => $load1,
            'load5' => round($load1 * 0.9, 2),
            'load15' => round($load1 * 0.8, 2),
        ];
    }
}
