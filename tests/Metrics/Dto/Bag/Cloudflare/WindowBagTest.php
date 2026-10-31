<?php

declare(strict_types=1);

namespace App\Tests\Metrics\Dto\Bag\Cloudflare;

use App\Metrics\Dto\Bag\Cloudflare\WindowBag;
use PHPUnit\Framework\TestCase;

final class WindowBagTest extends TestCase
{
    public function testCacheHitRatioCountsHitRevalidatedStaleUpdating(): void
    {
        $window = new WindowBag(['requests' => 238, 'by_cache_status' => ['hit' => 19, 'miss' => 9, 'dynamic' => 207, 'revalidated' => 3]]);

        $this->assertEqualsWithDelta(22 / 238, $window->cacheHitRatio(), 0.0001);
    }

    public function testCacheHitRatioIsNullWithoutRequests(): void
    {
        $this->assertNull(new WindowBag(['requests' => 0, 'by_cache_status' => []])->cacheHitRatio());
        $this->assertNull(new WindowBag([])->cacheHitRatio());
    }

    public function testStatusesAreGroupedByClass(): void
    {
        $window = new WindowBag(['by_status' => ['200' => 52, '202' => 171, '302' => 15, '000' => 1]]);

        $this->assertSame(['2xx' => 223, '3xx' => 15, '4xx' => 0, '5xx' => 0, 'other' => 1], $window->byStatusClass());
    }

    public function testMitigatedIgnoresPassiveActions(): void
    {
        $window = new WindowBag(['security' => ['by_action' => ['skip' => 171, 'unknown' => 67, 'block' => 31, 'managed_challenge' => 7, 'log' => 2, 'allow' => 4]]]);

        $this->assertSame(38, $window->mitigated());
        $this->assertSame(['skip' => 171, 'unknown' => 67, 'block' => 31, 'managed_challenge' => 7, 'log' => 2, 'allow' => 4], $window->securityByAction);
    }

    public function testTopKeepsFiveAndFoldsTheRest(): void
    {
        $counts = ['FR' => 100, 'DE' => 50, 'US' => 40, 'BE' => 30, 'CN' => 20, 'IT' => 10, 'ES' => 5];

        $this->assertSame(['FR' => 100, 'DE' => 50, 'US' => 40, 'BE' => 30, 'CN' => 20, 'other' => 15], WindowBag::top($counts));
        $this->assertSame(['FR' => 100, 'DE' => 50, 'US' => 40], WindowBag::top(['US' => 40, 'FR' => 100, 'DE' => 50]));
        $this->assertSame([], WindowBag::top([]));
        $this->assertSame(['a' => 9, 'b' => 8, 'c' => 7, 'd' => 6, 'other' => 5 + 4 + 3], WindowBag::top(['a' => 9, 'b' => 8, 'c' => 7, 'd' => 6, 'other' => 5, 'e' => 4, 'f' => 3]));
    }

    public function testRequestsPerMinuteUsesTheWindowLength(): void
    {
        $window = new WindowBag(['from' => 1761916860, 'to' => 1761917760, 'requests' => 238]);

        $this->assertEqualsWithDelta(238 / 15, $window->requestsPerMinute(), 0.0001);
        $this->assertNull(new WindowBag(['from' => 10, 'to' => 10, 'requests' => 5])->requestsPerMinute());
    }
}
