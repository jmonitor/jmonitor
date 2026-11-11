<?php

declare(strict_types=1);

namespace App\Tests\Metrics\Dto\Bag\Cloudflare;

use App\Metrics\Dto\Bag\Cloudflare\ZoneBag;
use PHPUnit\Framework\TestCase;

final class ZoneBagTest extends TestCase
{
    public function testCertificateDaysLeft(): void
    {
        $now = new \DateTimeImmutable('2026-10-31 12:00:00 UTC');

        $zone = new ZoneBag(['certificate' => ['status' => 'active', 'expires_at' => $now->getTimestamp() + (int) (47.9 * 86400)]]);
        $this->assertSame(47, $zone->certificate?->daysLeft($now));

        $expired = new ZoneBag(['certificate' => ['status' => 'active', 'expires_at' => $now->getTimestamp() - 12 * 3600]]);
        $this->assertSame(-1, $expired->certificate?->daysLeft($now));

        $this->assertNull(new ZoneBag(['name' => 'x'])->certificate);
        $this->assertNull(new ZoneBag(['certificate' => ['status' => 'pending']])->certificate?->daysLeft($now));
    }

    public function testSettingsAbsenceIsVisible(): void
    {
        $this->assertFalse(new ZoneBag(['name' => 'x'])->hasSettings);
        $this->assertSame([], new ZoneBag(['name' => 'x'])->settings);

        $zone = new ZoneBag(['settings' => ['ssl' => 'full'], 'development_mode' => 3600]);
        $this->assertTrue($zone->hasSettings);
        $this->assertSame('full', $zone->settings['ssl']);
        $this->assertSame(3600, $zone->developmentMode);
    }
}
