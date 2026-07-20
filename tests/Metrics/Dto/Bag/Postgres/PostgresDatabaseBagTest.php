<?php

declare(strict_types=1);

namespace App\Tests\Metrics\Dto\Bag\Postgres;

use App\Entity\Project;
use App\Metrics\Consumer\Consumer;
use App\Metrics\Dto\Bag\Postgres\PostgresDatabaseBag;
use App\Metrics\Dto\MetricBagDto;
use PHPUnit\Framework\TestCase;

class PostgresDatabaseBagTest extends TestCase
{
    private function makeBag(array $data): PostgresDatabaseBag
    {
        $bag = MetricBagDto::create(
            $this->createMock(Project::class),
            Consumer::POSTGRES_DATABASE,
            1,
            $data,
            new \DateTimeImmutable(),
            false,
        );

        $this->assertInstanceOf(PostgresDatabaseBag::class, $bag);
        /** @var PostgresDatabaseBag $bag */
        return $bag;
    }

    public function testReadsTablesAndRatios(): void
    {
        $bag = $this->makeBag([
            'schema' => 'public',
            'db_size' => 1048576,
            'tables' => [
                'live_tuples' => 900,
                'dead_tuples' => 100,
                'seq_scans' => 20,
                'idx_scans' => 80,
                'total_size' => 524288,
                'indexes_size' => 131072,
            ],
        ]);

        $this->assertSame(1048576, $bag->dbSize);
        $this->assertSame(524288, $bag->totalSize);
        $this->assertSame(131072, $bag->indexesSize);
        $this->assertSame(10.0, $bag->deadTupleRatio);   // 100/(900+100)*100
        $this->assertSame(80.0, $bag->indexUsageRatio);  // 80/(20+80)*100
    }

    public function testReturnsNullWhenNoData(): void
    {
        $bag = $this->makeBag([]);

        $this->assertNull($bag->dbSize);
        $this->assertNull($bag->deadTupleRatio);
        $this->assertNull($bag->indexUsageRatio);
    }
}
