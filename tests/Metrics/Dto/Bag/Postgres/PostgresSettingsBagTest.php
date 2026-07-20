<?php

declare(strict_types=1);

namespace App\Tests\Metrics\Dto\Bag\Postgres;

use App\Entity\Project;
use App\Metrics\Consumer\Consumer;
use App\Metrics\Dto\Bag\Postgres\PostgresSettingsBag;
use App\Metrics\Dto\MetricBagDto;
use PHPUnit\Framework\TestCase;

class PostgresSettingsBagTest extends TestCase
{
    private function makeBag(array $data): PostgresSettingsBag
    {
        $bag = MetricBagDto::create(
            $this->createMock(Project::class),
            Consumer::POSTGRES_SETTINGS,
            1,
            $data,
            new \DateTimeImmutable(),
            false,
        );

        $this->assertInstanceOf(PostgresSettingsBag::class, $bag);
        /** @var PostgresSettingsBag $bag */
        return $bag;
    }

    public function testReadsMemorySettingsAsBytes(): void
    {
        // The collector now sends memory settings already converted to bytes.
        $bag = $this->makeBag([
            'shared_buffers' => 134217728,        // 128 MiB
            'effective_cache_size' => 4294967296, // 4 GiB
            'work_mem' => 4194304,                // 4 MiB
            'maintenance_work_mem' => 67108864,   // 64 MiB
            'max_wal_size' => 1073741824,         // 1 GiB
            'max_connections' => '100',
        ]);

        $this->assertSame(134217728, $bag->sharedBuffers);
        $this->assertSame(4294967296, $bag->effectiveCacheSize);
        $this->assertSame(4194304, $bag->workMem);
        $this->assertSame(67108864, $bag->maintenanceWorkMem);
        $this->assertSame(1073741824, $bag->maxWalSize);
        $this->assertSame(100, $bag->maxConnections);
    }

    public function testReadsNonMemorySettings(): void
    {
        $bag = $this->makeBag([
            'server_version' => '16.2 (Debian)',
            'wal_level' => 'replica',
            'autovacuum' => 'on',
            'track_counts' => 'on',
            'autovacuum_vacuum_scale_factor' => '0.2',
            'log_min_duration_statement' => '-1',
            'TimeZone' => 'UTC',
        ]);

        $this->assertSame('16.2 (Debian)', $bag->serverVersion);
        $this->assertSame('16.2', $bag->semanticServerVersion);
        $this->assertSame(16, $bag->majorVersion);
        $this->assertSame('replica', $bag->walLevel);
        $this->assertSame('on', $bag->autovacuum);
        $this->assertSame('on', $bag->trackCounts);
        $this->assertSame('0.2', $bag->autovacuumVacuumScaleFactor);
        $this->assertSame('-1', $bag->logMinDurationStatement);
        $this->assertSame('UTC', $bag->timeZone);
    }

    public function testReturnsNullWhenNoData(): void
    {
        $bag = $this->makeBag([]);

        $this->assertNull($bag->sharedBuffers);
        $this->assertNull($bag->maxConnections);
        $this->assertNull($bag->walLevel);
        $this->assertNull($bag->majorVersion);
    }
}
