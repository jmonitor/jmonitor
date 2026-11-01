<?php

declare(strict_types=1);

namespace App\Tests\Metrics\Renderer;

use App\Metrics\Metric;
use Symfony\Component\DomCrawler\Crawler;

final class CloudflareExperienceAndStorageCardsTest extends CloudflareCardTestCase
{
    public function testWebVitalsAreRatedAndFormatted(): void
    {
        $html = $this->render(Metric::CloudflareWebVitals, self::fixture());
        $tiles = [];
        new Crawler($html)->filter('[data-vital]')->each(function (Crawler $tile) use (&$tiles): void {
            $tiles[$tile->attr('data-vital')] = [trim($tile->filter('.vital-value')->text()), $tile->filter('.vital-value')->attr('class')];
        });

        $this->assertSame('2.6 s', $tiles['lcp'][0]);
        $this->assertStringContainsString('text-warning', $tiles['lcp'][1]);
        $this->assertSame('48 ms', $tiles['inp'][0]);
        $this->assertStringContainsString('text-success', $tiles['inp'][1]);
        $this->assertSame('0.09', $tiles['cls'][0]);
        $this->assertSame('898 ms', $tiles['ttfb'][0]);
        $this->assertStringContainsString('text-warning', $tiles['ttfb'][1]);
    }

    public function testAMissingVitalIsShownAsUnknown(): void
    {
        $payload = self::fixture();
        unset($payload['web_analytics']['vitals']['inp_p75_ms']);

        $html = $this->render(Metric::CloudflareWebVitals, $payload);

        $this->assertSame('–', trim(new Crawler($html)->filter('[data-vital="inp"] .vital-value')->text()));
    }

    public function testPageLoadsTimingsAndHosts(): void
    {
        $lines = self::lines($this->render(Metric::CloudflarePageLoads, self::fixture()));
        $this->assertSame('11', $lines['Visits']);
        $this->assertSame('827 ms', $lines['Page load P50']);
        $this->assertSame('2.88 s', $lines['Page load P75']);

        $timings = self::lines($this->render(Metric::CloudflareTimings, self::fixture()));
        $this->assertSame('33 ms', $timings['DNS']);
        $this->assertSame('624 ms', $timings['Page render']);

        $hosts = self::lines($this->render(Metric::CloudflareWebAnalyticsByHost, self::fixture()));
        $this->assertStringStartsWith('7', $hosts['dash.jmonitor.io']);
    }

    public function testR2QuotasAndBuckets(): void
    {
        $payload = self::fixture();
        $payload['r2']['buckets'][0]['payload_bytes'] = 2 * 1024 ** 3;
        $payload['r2']['class_a_operations'] = 12000;

        $html = preg_replace('/\s+/', ' ', strip_tags($this->render(Metric::CloudflareR2, $payload))) ?? '';

        $this->assertStringContainsString('2 GiB', $html);
        $this->assertStringContainsString('10 GiB', $html);
        $this->assertStringContainsString('12 000', $html);
        $this->assertStringContainsString('1 000 000', $html);
        $this->assertStringContainsString('public', $html);
        $this->assertStringContainsString('2026-11-01', $html);
    }

    public function testImagesQuotas(): void
    {
        $html = $this->render(Metric::CloudflareImages, self::fixture());

        $this->assertStringContainsString('136', $html);
        $this->assertStringContainsString('5 000', $html);
        $this->assertStringContainsString('591', $html);
        $this->assertStringContainsString('100 000', $html);
        $this->assertStringContainsString('162', $html);

        $payload = self::fixture();
        unset($payload['images']['stored_quota']);
        $this->assertStringNotContainsString('100 000', $this->render(Metric::CloudflareImages, $payload));
    }

    public function testAnImagesQuotaNearlyUsedTurnsYellow(): void
    {
        $payload = self::fixture();
        $payload['images']['unique_transformations'] = 4200;

        $this->assertStringContainsString('bg-warning', $this->render(Metric::CloudflareImages, $payload));

        $payload['images']['unique_transformations'] = 5100;
        $this->assertStringContainsString('bg-danger', $this->render(Metric::CloudflareImages, $payload));
    }

    public function testNotConfiguredStorageSaysSo(): void
    {
        $payload = self::fixture();
        unset($payload['r2'], $payload['images']);
        $payload['unavailable'] = ['r2' => 'not_configured', 'images' => 'not_configured'];

        $this->assertStringContainsString('Not configured on this Cloudflare account.', $this->render(Metric::CloudflareR2, $payload));
        $this->assertStringContainsString('Not configured on this Cloudflare account.', $this->render(Metric::CloudflareImages, $payload));
    }
}
