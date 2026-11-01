<?php

declare(strict_types=1);

namespace App\Tests\Metrics\Renderer;

use App\Metrics\Metric;

final class CloudflareZoneCardsTest extends CloudflareCardTestCase
{
    public function testCertificateDaysLeftAndBadge(): void
    {
        $payload = self::fixture();
        $payload['zone']['certificate']['expires_at'] = time() + 47 * 86400 + 3600;
        $html = $this->render(Metric::CloudflareCertificate, $payload);
        $this->assertStringContainsString('>47<', preg_replace('/\s+/', '', $html) ?? '');
        $this->assertStringContainsString('badge-success', $html);

        $payload['zone']['certificate']['expires_at'] = time() + 10 * 86400 + 3600;
        $this->assertStringContainsString('badge-warning', $this->render(Metric::CloudflareCertificate, $payload));

        $payload['zone']['certificate']['status'] = 'pending';
        $this->assertStringContainsString('badge-danger', $this->render(Metric::CloudflareCertificate, $payload));
    }

    public function testAMissingCertificateKeyNamesBothCauses(): void
    {
        $payload = self::fixture();
        unset($payload['zone']['certificate']);

        $html = $this->render(Metric::CloudflareCertificate, $payload);

        $this->assertStringContainsString('No active edge certificate', $html);
        $this->assertStringContainsString('SSL and Certificates permission', $html);
        $this->assertStringContainsString('https://github.com/jmonitor/collector?tab=readme-ov-file#cloudflare', $html);
    }

    public function testAMissingSettingsKeyShowsTheForbiddenCopy(): void
    {
        $payload = self::fixture();
        unset($payload['zone']['settings']);

        $this->assertStringContainsString('lacks the permission', $this->render(Metric::CloudflareSettings, $payload));
        $this->assertStringContainsString('lacks the permission', $this->render(Metric::CloudflareCacheSettings, $payload));
    }

    public function testTheZoneCardFlagsDevelopmentMode(): void
    {
        $payload = self::fixture();
        $this->assertStringContainsString('jmonitor.io', $this->render(Metric::CloudflareZone, $payload));
        $this->assertStringNotContainsString('badge-danger', $this->render(Metric::CloudflareZone, $payload));

        $payload['zone']['development_mode'] = 3600;
        $this->assertStringContainsString('badge-danger', $this->render(Metric::CloudflareZone, $payload));
    }

    public function testTheWholePageIsDownWhenSkipped(): void
    {
        $html = $this->render(Metric::CloudflareZone, [], skipped: true);

        $this->assertStringContainsString('could not start the Cloudflare collector', $html);
    }

    public function testAnUnavailableZoneShowsItsReason(): void
    {
        $html = $this->render(Metric::CloudflareZone, ['unavailable' => ['zone' => 'rate_limited']]);

        $this->assertStringContainsString('rate limit reached', $html);
    }
}
