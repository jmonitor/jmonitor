<?php

declare(strict_types=1);

namespace App\Tests\Metrics\Dto\Bag\Cloudflare;

use App\Metrics\Dto\Bag\Cloudflare\TrafficBag;
use App\Metrics\Dto\Bag\Cloudflare\TrafficMinute;
use PHPUnit\Framework\TestCase;

final class TrafficBagTest extends TestCase
{
    public function testMinutesWithoutIntegerTimeAreDropped(): void
    {
        $traffic = new TrafficBag(['minutes' => [
            ['time' => 1761917460, 'requests' => 13],
            ['requests' => 2],
            ['time' => 'nope', 'requests' => 2],
        ]]);

        $this->assertCount(1, $traffic->minutes);
        $this->assertSame(1761917460, $traffic->minutes[0]->time);
    }

    public function testLatestOriginSkipsCacheOnlyMinutes(): void
    {
        $traffic = new TrafficBag(['minutes' => [
            ['time' => 1, 'origin_response_ms_p50' => 30, 'origin_response_ms_p95' => 56],
            ['time' => 2, 'origin_response_ms_p50' => null, 'origin_response_ms_p95' => null],
        ]]);

        $this->assertSame(1, $traffic->latestOrigin()?->time);
        $this->assertNull(new TrafficBag([])->latestOrigin());
    }

    public function testMinuteDerivedValues(): void
    {
        $minute = TrafficMinute::fromArray(['time' => 1, 'requests' => 13, 'cached_requests' => 3, 'status_4xx_ratio' => 0.25, 'origin_response_ms_p50' => 30]);
        $this->assertNotNull($minute);

        $this->assertSame(10, $minute->uncachedRequests());
        $this->assertEqualsWithDelta(3 / 13, $minute->cacheHitRatio(), 0.0001);
        $this->assertSame(0.25, $minute->status4xxRatio);
        $this->assertSame(30.0, $minute->originResponseMsP50);
        $this->assertNull($minute->originResponseMsP95);
        $this->assertNull(TrafficMinute::fromArray(['time' => 1, 'requests' => 0])?->cacheHitRatio());
    }
}
