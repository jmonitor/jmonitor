<?php

declare(strict_types=1);

namespace App\Demo\Generator;

use App\Demo\State\DemoState;
use App\Metrics\Consumer\Consumer;

class SymfonyGenerator implements DemoMetricGeneratorInterface
{
    public function getConsumer(): Consumer
    {
        return Consumer::SYMFONY;
    }

    public function generate(DemoState $state): array
    {
        return [
            'version' => '8.0.0',
            'env' => 'prod',
            'debug' => false,
            'bundles' => [
                'Symfony\\Bundle\\FrameworkBundle\\FrameworkBundle',
                'Symfony\\Bundle\\TwigBundle\\TwigBundle',
                'Doctrine\\Bundle\\DoctrineBundle\\DoctrineBundle',
                'App\\Kernel',
            ],
            'project_dir' => '/app',
            'cache_dir' => '/app/var/cache/prod',
            'log_dir' => '/app/var/log',
            'build_dir' => '/app/var/cache/prod/build',
            'share_dir' => '/app/var/share',
            'charset' => 'UTF-8',
            'components' => [
                'scheduler' => [
                    // #[AsPeriodicTask(frequency: '1 hour', jitter: 10)] — Symfony renders the
                    // trigger as "every <interval> with 0-<max> second jitter".
                    [
                        'trigger' => 'every 1 hour with 0-10 second jitter',
                        'command' => 'app:cleanup-cache',
                        'next_run' => time() + 2400,
                        'description' => 'Clean up expired cache entries',
                    ],
                    // #[AsPeriodicTask(frequency: '6 hours', jitter: 10)]
                    [
                        'trigger' => 'every 6 hours with 0-10 second jitter',
                        'command' => 'app:refresh-eol',
                        'next_run' => time() + 16200,
                        'description' => 'Refresh components end-of-life data',
                    ],
                    // Crontab notation, no jitter.
                    [
                        'trigger' => '0 2 * * *',
                        'command' => 'app:backup-database',
                        'next_run' => time() + 39600,
                        'description' => 'Nightly database backup',
                    ],
                ],
                'flex_recipes' => [
                    'up_to_date' => false,
                    'outdated_recipes' => [
                        'symfony/stimulus-bundle (update available)',
                    ],
                ],
                // Mirrors `messenger:stats --format=json`: countable transports carry a
                // `count`, while sync/scheduler transports are listed as uncountable.
                'messenger' => [
                    'transports' => [
                        // Worker keeps up: async queue stays drained (renders blank, not "0").
                        'async' => ['count' => 0],
                        // A few messages parked in the dead-letter transport.
                        'failed' => ['count' => 3],
                    ],
                    'uncountable_transports' => ['sync', 'scheduler_default'],
                ],
            ],
        ];
    }
}
