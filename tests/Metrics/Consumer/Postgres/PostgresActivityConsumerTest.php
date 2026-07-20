<?php

declare(strict_types=1);

namespace App\Tests\Metrics\Consumer\Postgres;

use App\Entity\Project;
use App\Metrics\Consumer\Consumer;
use App\Metrics\Consumer\Postgres\PostgresActivityConsumer;
use App\Metrics\DeltaCalculator;
use App\Metrics\Dto\MetricBagDto;
use InfluxDB2\Point;
use PHPUnit\Framework\TestCase;

class PostgresActivityConsumerTest extends TestCase
{
    public function testGetInfluxPointsPushesCountersAndGauges(): void
    {
        $consumer = new PostgresActivityConsumer($this->createMock(DeltaCalculator::class));

        $bag = MetricBagDto::create(
            $this->createMock(Project::class),
            Consumer::POSTGRES_ACTIVITY,
            1,
            [
                'database_stats' => [
                    'numbackends' => 5, 'xact_commit' => 1000, 'xact_rollback' => 10,
                    'blks_read' => 50, 'blks_hit' => 9950, 'tup_inserted' => 3,
                    'tup_updated' => 4, 'tup_deleted' => 1, 'deadlocks' => 2,
                    'temp_files' => 7, 'temp_bytes' => 8192,
                ],
                'connections' => ['active' => 2, 'idle' => 3, 'idle in transaction' => 1],
                'sessions' => ['blocked_count' => 2, 'oldest_transaction_seconds' => 42],
            ],
            new \DateTimeImmutable(),
            false,
        );

        $points = $consumer->getInfluxPoints($bag);

        $this->assertCount(1, $points);
        $this->assertInstanceOf(Point::class, $points[0]);
        $line = (string) $points[0]->toLineProtocol();
        $this->assertStringContainsString('postgres_activity', $line);
        $this->assertStringContainsString('xact_commit=1000', $line);
        $this->assertStringContainsString('numbackends=5', $line);
        $this->assertStringContainsString('conn_active=2', $line);
        $this->assertStringContainsString('conn_idle_in_transaction=1', $line);
        $this->assertStringContainsString('blocked_count=2', $line);
    }

    public function testGetInfluxPointsEmptyWhenNotAPostgresActivityBag(): void
    {
        $consumer = new PostgresActivityConsumer($this->createMock(DeltaCalculator::class));

        // A plain MetricBagDto (not a PostgresActivityBag) must return no points.
        $bag = $this->createMock(MetricBagDto::class);

        $this->assertSame([], $consumer->getInfluxPoints($bag));
    }

    public function testBlockedCountZeroIsWritten(): void
    {
        $consumer = new PostgresActivityConsumer($this->createMock(DeltaCalculator::class));

        $bag = MetricBagDto::create(
            $this->createMock(Project::class),
            Consumer::POSTGRES_ACTIVITY,
            1,
            ['database_stats' => ['numbackends' => 1], 'sessions' => ['blocked_count' => 0]],
            new \DateTimeImmutable(),
            false,
        );

        $line = (string) $consumer->getInfluxPoints($bag)[0]->toLineProtocol();
        $this->assertStringContainsString('blocked_count=0', $line);
    }
}
