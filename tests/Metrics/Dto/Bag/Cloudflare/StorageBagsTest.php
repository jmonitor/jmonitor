<?php

declare(strict_types=1);

namespace App\Tests\Metrics\Dto\Bag\Cloudflare;

use App\Metrics\Dto\Bag\Cloudflare\ImagesBag;
use App\Metrics\Dto\Bag\Cloudflare\R2Bag;
use PHPUnit\Framework\TestCase;

final class StorageBagsTest extends TestCase
{
    public function testR2StorageSumsPayloadAndMetadata(): void
    {
        $r2 = new R2Bag(['buckets' => [
            ['name' => 'a', 'payload_bytes' => 100, 'metadata_bytes' => 5, 'object_count' => 2],
            ['name' => 'b', 'payload_bytes' => 200, 'metadata_bytes' => 10],
        ]]);

        $this->assertSame(315, $r2->storageBytes());
        $this->assertSame('a', $r2->buckets[0]->name);
        $this->assertSame(2, $r2->buckets[0]->objectCount);
        $this->assertSame(0, new R2Bag([])->storageBytes());
        $this->assertSame(10 * 1024 ** 3, R2Bag::FREE_STORAGE_BYTES);
    }

    public function testResetsAtIsTheFirstOfNextMonthUtc(): void
    {
        $this->assertSame('2026-11-01T00:00:00+00:00', new R2Bag(['month' => '2026-10'])->resetsAt()?->format(\DateTimeInterface::ATOM));
        $this->assertSame('2027-01-01T00:00:00+00:00', new ImagesBag(['month' => '2026-12'])->resetsAt()?->format(\DateTimeInterface::ATOM));
        $this->assertNull(new ImagesBag(['month' => 'nope'])->resetsAt());
        $this->assertNull(new ImagesBag([])->resetsAt());
    }

    public function testImagesValues(): void
    {
        $images = new ImagesBag(['stored' => 591, 'stored_quota' => 100000, 'unique_transformations' => 136, 'requests_last_24h' => 162]);

        $this->assertSame(591, $images->stored);
        $this->assertSame(100000, $images->storedQuota);
        $this->assertSame(136, $images->uniqueTransformations);
        $this->assertSame(162, $images->requestsLast24h);
        $this->assertNull(new ImagesBag([])->storedQuota);
        $this->assertSame(5000, ImagesBag::FREE_UNIQUE_TRANSFORMATIONS);
    }
}
