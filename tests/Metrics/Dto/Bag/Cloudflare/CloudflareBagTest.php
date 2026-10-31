<?php

declare(strict_types=1);

namespace App\Tests\Metrics\Dto\Bag\Cloudflare;

use App\Entity\Project;
use App\Metrics\Consumer\Consumer;
use App\Metrics\Dto\Bag\Cloudflare\CloudflareBag;
use App\Metrics\Dto\Bag\Cloudflare\ZoneBag;
use App\Metrics\Dto\MetricBagDto;
use PHPUnit\Framework\TestCase;

final class CloudflareBagTest extends TestCase
{
    public function testBlocksAreNullWhenAbsent(): void
    {
        $bag = $this->bag(['unavailable' => ['r2' => 'forbidden']]);

        $this->assertNull($bag->r2);
        $this->assertNull($bag->zone);
        $this->assertSame('forbidden', $bag->unavailableReason('r2'));
        $this->assertNull($bag->unavailableReason('today'));
    }

    public function testBlocksAreBuiltFromThePayload(): void
    {
        $bag = $this->bag(self::fixture());

        $this->assertInstanceOf(ZoneBag::class, $bag->zone);
        $this->assertSame('jmonitor.io', $bag->zone->name);
        $this->assertSame(238, $bag->traffic?->window->requests);
        $this->assertSame(12994, $bag->today?->requests);
        $this->assertSame(11, $bag->webAnalytics?->pageViews);
        $this->assertNotNull($bag->r2);
        $this->assertCount(1, $bag->r2->buckets);
        $this->assertSame(591, $bag->images?->stored);
        $this->assertFalse($bag->isCollectorDown());
    }

    public function testUnavailableDefaultsToEmptyAndNoZoneMeansDown(): void
    {
        $bag = $this->bag([]);

        $this->assertSame([], $bag->unavailable);
        $this->assertTrue($bag->isCollectorDown());
    }

    public function testASkippedBagIsDown(): void
    {
        $this->assertTrue($this->bag(self::fixture(), skipped: true)->isCollectorDown());
    }

    public function testAZoneWithAReasonIsNotDown(): void
    {
        $this->assertFalse($this->bag(['unavailable' => ['zone' => 'rate_limited']])->isCollectorDown());
    }

    /**
     * @return array<string, mixed>
     */
    public static function fixture(): array
    {
        return json_decode((string) file_get_contents(__DIR__ . '/../../../Consumer/Cloudflare/fixtures/payload_v1.json'), true, flags: JSON_THROW_ON_ERROR);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function bag(array $payload, bool $skipped = false): CloudflareBag
    {
        $bag = MetricBagDto::create($this->createMock(Project::class), Consumer::CLOUDFLARE, 1, $payload, new \DateTimeImmutable(), false, $skipped);
        $this->assertInstanceOf(CloudflareBag::class, $bag);

        return $bag;
    }
}
