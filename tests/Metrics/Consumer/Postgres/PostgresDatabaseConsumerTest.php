<?php

declare(strict_types=1);

namespace App\Tests\Metrics\Consumer\Postgres;

use App\Entity\Project;
use App\Metrics\Consumer\Consumer;
use App\Metrics\Consumer\Postgres\PostgresDatabaseConsumer;
use App\Metrics\Dto\MetricBagDto;
use InfluxDB2\Point;
use PHPUnit\Framework\TestCase;

class PostgresDatabaseConsumerTest extends TestCase
{
    public function testGetInfluxPoints(): void
    {
        $consumer = new PostgresDatabaseConsumer();

        $bag = MetricBagDto::create(
            $this->createMock(Project::class),
            Consumer::POSTGRES_DATABASE,
            1,
            [
                'schema' => 'public',
                'db_size' => 1048576,
                'tables' => [
                    'live_tuples' => 900, 'dead_tuples' => 100,
                    'seq_scans' => 20, 'idx_scans' => 80,
                    'total_size' => 524288, 'indexes_size' => 131072,
                ],
            ],
            new \DateTimeImmutable(),
            false,
        );

        $points = $consumer->getInfluxPoints($bag);

        $this->assertCount(1, $points);
        $this->assertInstanceOf(Point::class, $points[0]);
        $line = (string) $points[0]->toLineProtocol();
        $this->assertStringContainsString('postgres_database', $line);
        $this->assertStringContainsString('db_size=1048576', $line);
        $this->assertStringContainsString('seq_scans=20', $line);
        $this->assertStringContainsString('idx_scans=80', $line);
    }

    public function testGetInfluxPointsEmptyWhenNoData(): void
    {
        $consumer = new PostgresDatabaseConsumer();

        $bag = MetricBagDto::create(
            $this->createMock(Project::class),
            Consumer::POSTGRES_DATABASE,
            1,
            [],
            new \DateTimeImmutable(),
            false,
        );

        $this->assertSame([], $consumer->getInfluxPoints($bag));
    }
}
