<?php

declare(strict_types=1);

namespace App\Demo\Generator\Postgres;

use App\Demo\Generator\DemoMetricGeneratorInterface;
use App\Demo\State\DemoState;
use App\Metrics\Consumer\Consumer;

class SlowQueriesGenerator implements DemoMetricGeneratorInterface
{
    private const array QUERY_SAMPLES = [
        'SELECT * FROM orders o JOIN order_items oi ON oi.order_id = o.id WHERE o.created_at >= $1 ORDER BY o.created_at DESC',
        'SELECT count(*) FROM products WHERE category_id = $1 AND stock > $2',
        'UPDATE products SET stock = stock - $1 WHERE id = $2',
        'SELECT u.*, sum(o.total) FROM users u LEFT JOIN orders o ON o.user_id = u.id GROUP BY u.id ORDER BY sum(o.total) DESC LIMIT $1',
        'SELECT * FROM product_reviews WHERE product_id = $1 ORDER BY created_at DESC',
        'DELETE FROM sessions WHERE last_activity < $1',
    ];

    public function getConsumer(): Consumer
    {
        return Consumer::POSTGRES_SLOW_QUERIES;
    }

    public function generate(DemoState $state): array
    {
        $slowQueries = [];

        foreach (self::QUERY_SAMPLES as $sample) {
            $execCount = rand(50, 50000);
            $avgTimeMs = rand(50, 25000) / 100;
            $blksHit = rand(1000, 500000);

            $slowQueries[] = [
                'query_sample' => $sample,
                'exec_count' => $execCount,
                'total_time_ms' => round($avgTimeMs * $execCount, 2),
                'avg_time_ms' => $avgTimeMs,
                'max_time_ms' => round($avgTimeMs * (rand(150, 400) / 100), 2),
                'stddev_time_ms' => round($avgTimeMs * (rand(10, 60) / 100), 2),
                'rows' => rand(0, 10000),
                'shared_blks_hit' => $blksHit,
                'shared_blks_read' => rand(0, (int) round($blksHit * 0.05)),
            ];
        }

        usort($slowQueries, static fn(array $a, array $b): int => $b['avg_time_ms'] <=> $a['avg_time_ms']);

        return [
            'min_calls' => 1,
            'min_avg_time_ms' => 0,
            'limit' => 10,
            'order_by' => 'avg',
            'slow_queries' => $slowQueries,
        ];
    }
}
