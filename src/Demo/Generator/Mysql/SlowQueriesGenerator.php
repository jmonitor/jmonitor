<?php

declare(strict_types=1);

namespace App\Demo\Generator\Mysql;

use App\Demo\Generator\DemoMetricGeneratorInterface;
use App\Demo\State\DemoState;
use App\Metrics\Consumer\Consumer;

class SlowQueriesGenerator implements DemoMetricGeneratorInterface
{
    private const array SAMPLES = [
        'SELECT * FROM orders o JOIN order_items oi ON oi.order_id = o.id WHERE o.created_at >= ? ORDER BY o.created_at DESC',
        'SELECT count(*) FROM products WHERE category_id = ? AND stock > ?',
        'UPDATE products SET stock = stock - ? WHERE id = ?',
        'SELECT u.*, sum(o.total) FROM users u LEFT JOIN orders o ON o.user_id = u.id GROUP BY u.id ORDER BY sum(o.total) DESC LIMIT ?',
        'SELECT * FROM product_reviews WHERE product_id = ? ORDER BY created_at DESC',
    ];

    public function getConsumer(): Consumer
    {
        return Consumer::MYSQL_SLOW_QUERIES;
    }

    public function generate(DemoState $state): array
    {
        $slow = [];
        foreach (self::SAMPLES as $sample) {
            $execCount = rand(5, 5000);
            $avg = rand(100, 25000) / 100; // 1ms .. 250ms
            $slow[] = [
                'query_sample' => $sample,
                'exec_count' => $execCount,
                'total_time_ms' => round($avg * $execCount, 2),
                'avg_time_ms' => $avg,
                'max_time_ms' => round($avg * (rand(150, 400) / 100), 2),
            ];
        }

        usort($slow, static fn(array $a, array $b): int => $b['avg_time_ms'] <=> $a['avg_time_ms']);

        return [
            'schema_name' => 'demo_shop',
            'min_exec_count' => 5,
            'min_avg_time_ms' => 1,
            'limit' => 10,
            'order_by' => 'avg',
            'slow_queries' => $slow,
        ];
    }
}
