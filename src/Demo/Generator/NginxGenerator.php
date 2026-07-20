<?php

declare(strict_types=1);

namespace App\Demo\Generator;

use App\Demo\State\DemoState;
use App\Metrics\Consumer\Consumer;

class NginxGenerator implements DemoMetricGeneratorInterface
{
    public function getConsumer(): Consumer
    {
        return Consumer::NGINX;
    }

    public function generate(DemoState $state): array
    {
        $season = $state->seasonality();
        $waiting = (int) round($state->walk('nginx.waiting', 0, 100, 0.1) * $season);
        $writing = (int) round($state->walk('nginx.writing', 0, 50, 0.15) * $season);
        $reading = (int) round($state->walk('nginx.reading', 0, 50, 0.15) * $season);
        $requests = (int) round(rand(50, 1000) * $season);

        return [
            'version' => '1.29.5',
            'modules' => ['http_ssl_module', 'http_stub_status_module'],
            'status' => [
                'active' => $waiting + $writing + $reading,
                'accepts' => (int) $state->counter('nginx.accepts', (int) round($requests * 0.9)),
                'handled' => (int) $state->counter('nginx.handled', (int) round($requests * 0.9)),
                'requests' => (int) $state->counter('nginx.requests', $requests),
                'reading' => $reading,
                'writing' => $writing,
                'waiting' => $waiting,
            ],
            'config' => [
                'config_path' => '/etc/nginx/nginx.conf',
                'user' => 'www-data',
                'worker_processes' => '4',
                'include' => ['/etc/nginx/conf.d/*.conf', '/etc/nginx/sites-enabled/*'],
                'worker_connections' => 1024,
                'sendfile' => 'on',
                'tcp_nopush' => 'on',
                'tcp_nodelay' => 'on',
                'keepalive_timeout' => 65,
                'types_hash_max_size' => 2048,
                'server_tokens' => 'off',
                'ssl_protocols' => 'TLSv1.2 TLSv1.3',
                'ssl_prefer_server_ciphers' => 'on',
                'access_log' => '/var/log/nginx/access.log',
                'error_log' => '/var/log/nginx/error.log warn',
                'gzip' => 'on',
            ],
            'cpu_count' => 4,
        ];
    }
}
