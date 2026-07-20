<?php

declare(strict_types=1);

namespace App\Tests\Metrics\Dto\Bag\Postgres;

use App\Entity\Project;
use App\Metrics\Consumer\Consumer;
use App\Metrics\Dto\Bag\Postgres\PostgresActivityBag;
use App\Metrics\Dto\MetricBagDto;
use PHPUnit\Framework\TestCase;

class PostgresActivityBagTest extends TestCase
{
    private function makeBag(array $data): PostgresActivityBag
    {
        $bag = MetricBagDto::create(
            $this->createMock(Project::class),
            Consumer::POSTGRES_ACTIVITY,
            1,
            $data,
            new \DateTimeImmutable(),
            false,
        );

        $this->assertInstanceOf(PostgresActivityBag::class, $bag);
        /** @var PostgresActivityBag $bag */
        return $bag;
    }

    public function testReadsDatabaseStats(): void
    {
        $bag = $this->makeBag([
            'database_stats' => [
                'numbackends' => 5,
                'xact_commit' => 1000,
                'xact_rollback' => 10,
                'blks_read' => 50,
                'blks_hit' => 9950,
                'tup_inserted' => 3,
                'tup_updated' => 4,
                'tup_deleted' => 1,
                'deadlocks' => 2,
                'temp_files' => 7,
                'temp_bytes' => 8192,
            ],
            'connections' => ['active' => 2, 'idle' => 3, 'idle in transaction' => 1],
            'sessions' => [
                'oldest_transaction_seconds' => 42,
                'idle_in_transaction_count' => 1,
                'oldest_idle_in_transaction_seconds' => 30,
                'blocked_count' => 2,
                'max_wait_seconds' => 12,
                'blocked_queries' => [['blocked_pid' => 1]],
            ],
        ]);

        $this->assertSame(5, $bag->numbackends);
        $this->assertSame(1000, $bag->xactCommit);
        $this->assertSame(10, $bag->xactRollback);
        $this->assertSame(2, $bag->deadlocks);
        $this->assertSame(99.5, $bag->cacheHitRatio);
        $this->assertSame(0.99, $bag->rollbackRatio);
        $this->assertSame(['active' => 2, 'idle' => 3, 'idle in transaction' => 1], $bag->connections);
        $this->assertSame(42, $bag->oldestTransactionSeconds);
        $this->assertSame(1, $bag->idleInTransactionCount);
        $this->assertSame(30, $bag->oldestIdleInTransactionSeconds);
        $this->assertSame(2, $bag->blockedCount);
        $this->assertSame(12, $bag->maxWaitSeconds);
        $this->assertCount(1, $bag->blockedQueries);
    }

    public function testReturnsNullRatiosWhenNoData(): void
    {
        $bag = $this->makeBag([]);

        $this->assertNull($bag->numbackends);
        $this->assertNull($bag->cacheHitRatio);
        $this->assertNull($bag->rollbackRatio);
        $this->assertSame([], $bag->connections);
        $this->assertSame(0, $bag->blockedCount);
        $this->assertSame([], $bag->blockedQueries);
    }
}
