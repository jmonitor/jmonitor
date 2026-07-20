<?php

declare(strict_types=1);

namespace App\Demo\Generator;

use App\Demo\State\DemoState;
use App\Metrics\Consumer\Consumer;

class SystemGenerator implements DemoMetricGeneratorInterface
{
    private const int DISK_TOTAL = 80 * 1024 * 1024 * 1024;   // 80 GiB
    private const int RAM_TOTAL = 8 * 1024 * 1024 * 1024;     // 8 GiB

    public function getConsumer(): Consumer
    {
        return Consumer::SYSTEM;
    }

    public function generate(DemoState $state): array
    {
        $season = $state->seasonality();

        $diskUsedPercent = $state->walk('system.disk_used', 35.0, 75.0, 0.02);
        $ramUsedPercent = $state->walk('system.ram_used', 30.0, 85.0, 0.05) * $season + 10;
        $ramUsedPercent = min(95.0, $ramUsedPercent);
        $load1 = round($state->walk('system.load1', 0.1, 3.5, 0.1) * $season, 2);

        return [
            'disk' => [
                'total' => self::DISK_TOTAL,
                'free' => (int) round(self::DISK_TOTAL * (1 - $diskUsedPercent / 100)),
            ],
            'cpu' => [
                'cores' => 4,
                'load' => (int) round($state->walk('system.cpu', 5.0, 90.0, 0.08) * $season),
                'load1' => $load1,
                'load5' => round($load1 * 0.9, 2),
                'load15' => round($load1 * 0.8, 2),
            ],
            'ram' => [
                'total' => self::RAM_TOTAL,
                'available' => (int) round(self::RAM_TOTAL * (1 - $ramUsedPercent / 100)),
            ],
            'os' => [
                'pretty_name' => 'Debian GNU/Linux 12 (bookworm)',
                'uptime' => 3600 * 24 * 17,
            ],
            'time' => time(),
            'timezone' => 'UTC',
            'hostname' => 'demo-web-01',
        ];
    }
}
