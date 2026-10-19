<?php

declare(strict_types=1);

namespace App\Tests\Metrics\Dto\Bag\Mysql;

use App\Entity\Project;
use App\Metrics\Consumer\Consumer;
use App\Metrics\Dto\Bag\Mysql\MysqlStatusBag;
use App\Metrics\Dto\MetricBagDto;
use PHPUnit\Framework\TestCase;

class MysqlStatusBagTest extends TestCase
{
    private function makeBag(array $data): MysqlStatusBag
    {
        $bag = MetricBagDto::create(
            $this->createMock(Project::class),
            Consumer::MYSQL_STATUS,
            1,
            $data,
            new \DateTimeImmutable(),
            false,
        );

        $this->assertInstanceOf(MysqlStatusBag::class, $bag);
        /** @var MysqlStatusBag $bag */
        return $bag;
    }

    public function testBufferPoolBytesUsedFromBytesFree(): void
    {
        $bag = $this->makeBag(['Innodb_buffer_pool_bytes_free' => 384 * 1024 * 1024]);

        $this->assertSame(640 * 1024 * 1024, $bag->getInnoDbBufferPoolBytesUsed(1024 * 1024 * 1024));
        $this->assertSame(62.5, $bag->getInnoDbBufferPoolUsage(1024 * 1024 * 1024));
    }

    public function testBufferPoolBytesUsedNeedsBytesFreeAndPoolSize(): void
    {
        $this->assertNull($this->makeBag([])->getInnoDbBufferPoolBytesUsed(1024));
        $this->assertNull($this->makeBag(['Innodb_buffer_pool_bytes_free' => 512])->getInnoDbBufferPoolBytesUsed(null));
    }

    public function testBufferPoolUsageFallsBackToPages(): void
    {
        $bag = $this->makeBag([
            'Innodb_buffer_pool_pages_total' => 100,
            'Innodb_buffer_pool_pages_free' => 25,
            'Innodb_page_size' => 16384,
        ]);

        $this->assertSame(75.0, $bag->getInnoDbBufferPoolUsage(1024));
    }
}
