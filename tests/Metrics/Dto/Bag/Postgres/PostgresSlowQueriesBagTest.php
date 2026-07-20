<?php

declare(strict_types=1);

namespace App\Tests\Metrics\Dto\Bag\Postgres;

use App\Entity\Project;
use App\Metrics\Consumer\Consumer;
use App\Metrics\Dto\Bag\Postgres\PostgresSlowQueriesBag;
use App\Metrics\Dto\MetricBagDto;
use PHPUnit\Framework\TestCase;

class PostgresSlowQueriesBagTest extends TestCase
{
    private function makeBag(array $data): PostgresSlowQueriesBag
    {
        $bag = MetricBagDto::create(
            $this->createMock(Project::class),
            Consumer::POSTGRES_SLOW_QUERIES,
            1,
            $data,
            new \DateTimeImmutable(),
            false,
        );

        $this->assertInstanceOf(PostgresSlowQueriesBag::class, $bag);
        /** @var PostgresSlowQueriesBag $bag */
        return $bag;
    }

    public function testReadsList(): void
    {
        $bag = $this->makeBag([
            'min_calls' => 1,
            'min_avg_time_ms' => 0,
            'limit' => 10,
            'order_by' => 'avg',
            'slow_queries' => [
                [
                    'query_sample' => 'SELECT * FROM users',
                    'exec_count' => 100,
                    'total_time_ms' => 2500.0,
                    'avg_time_ms' => 25.0,
                    'max_time_ms' => 80.0,
                    'stddev_time_ms' => 5.0,
                    'rows' => 100,
                    'shared_blks_hit' => 990,
                    'shared_blks_read' => 10,
                ],
            ],
        ]);

        $this->assertSame(10, $bag->limit);
        $this->assertCount(1, $bag->slowQueries);
        $query = $bag->slowQueries[0];
        $this->assertSame('SELECT * FROM users', $query->querySample);
        $this->assertSame(100, $query->execCount);
        $this->assertSame(25.0, $query->avgTimeMs);
        $this->assertSame(2.5, $query->totalTimeS);
        $this->assertSame(99.0, $query->cacheHitRatio); // 990/(990+10)*100
    }

    public function testNullWhenMetricNotSent(): void
    {
        $bag = $this->makeBag([]);

        $this->assertNull($bag->slowQueries);
    }
}
