<?php

declare(strict_types=1);

namespace App\Tests\Metrics\Dto\Bag\Cloudflare;

use App\Metrics\Dto\Bag\Cloudflare\VitalRating;
use App\Metrics\Dto\Bag\Cloudflare\WebAnalyticsBag;
use App\Metrics\Dto\Bag\Cloudflare\WebVital;
use PHPUnit\Framework\TestCase;

final class WebVitalTest extends TestCase
{
    public function testThresholds(): void
    {
        $this->assertSame(VitalRating::Good, WebVital::Lcp->rate(2400));
        $this->assertSame(VitalRating::NeedsImprovement, WebVital::Lcp->rate(2500));
        $this->assertSame(VitalRating::Poor, WebVital::Lcp->rate(4000));
        $this->assertSame(VitalRating::Good, WebVital::Cls->rate(0.09));
        $this->assertSame(VitalRating::Poor, WebVital::Inp->rate(500));
        $this->assertSame(VitalRating::NeedsImprovement, WebVital::Ttfb->rate(800));
        $this->assertNull(WebVital::Lcp->rate(null));
    }

    public function testTheBagReadsVitalsAndTimings(): void
    {
        $bag = new WebAnalyticsBag([
            'page_views' => 11,
            'vitals' => ['lcp_p75_ms' => 2624, 'inp_p75_ms' => 48, 'cls_p75' => 0.09, 'ttfb_p75_ms' => 898],
            'timings' => ['page_load_p75_ms' => 2884],
        ]);

        $this->assertSame(2624.0, $bag->vital(WebVital::Lcp));
        $this->assertSame(VitalRating::NeedsImprovement, $bag->rating(WebVital::Lcp));
        $this->assertSame(VitalRating::Good, $bag->rating(WebVital::Inp));
        $this->assertNull($bag->fcpP75Ms);
        $this->assertSame(2884.0, $bag->pageLoadP75Ms);
        $this->assertNull($bag->pageLoadP50Ms);
        $this->assertSame(11, $bag->pageViews);
    }
}
