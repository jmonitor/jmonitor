<?php

declare(strict_types=1);

namespace App\Tests\Metrics\Renderer;

use App\Metrics\Renderer\Dto\TimeSerieDto;
use App\Metrics\Metric;
use App\Metrics\Renderer;
use App\Metrics\Renderer\Dto\GaugeDto;
use Symfony\Component\DomCrawler\Crawler;

final class CloudflareWindowCardsTest extends CloudflareCardTestCase
{
    public function testQuickViewValuesFromTheFixture(): void
    {
        $payload = self::fixture();

        $requests = $this->render(Metric::CloudflareRequests, $payload);
        $this->assertStringContainsString('238', $requests);
        $this->assertStringContainsString('15.9', $requests);
        $this->assertStringNotContainsString('sampled', $requests);
        $this->assertStringContainsString('over 15 min', $requests);
        $this->assertStringNotContainsString('last 5 min', $requests);

        $gauge = $this->dto(Metric::CloudflareCacheHitRatio, Renderer::Gauge, $payload);
        $this->assertInstanceOf(GaugeDto::class, $gauge);
        $this->assertSame(9.2, $gauge->value);

        $origin = self::lines($this->render(Metric::CloudflareOriginResponseTime, $payload));
        $this->assertSame('56 ms', $origin['P95']);
        $this->assertSame(['P95'], array_keys($origin));
        $this->assertStringContainsString('>30<', preg_replace('/\s+/', '', $this->render(Metric::CloudflareOriginResponseTime, $payload)) ?? '');

        $security = $this->render(Metric::CloudflareSecurity, $payload);
        $this->assertStringContainsString('>0<', preg_replace('/\s+/', '', $security) ?? '');
        $this->assertStringNotContainsString('Top source', $security);
    }

    public function testASampledWindowSaysSo(): void
    {
        $payload = self::fixture();
        $payload['traffic']['window']['sampled'] = true;

        $this->assertStringContainsString('sampled', $this->render(Metric::CloudflareRequests, $payload));
    }

    public function testUnknownStatusCodesFoldIntoOther(): void
    {
        $payload = self::fixture();
        $payload['traffic']['window']['by_status']['000'] = 1;

        $lines = self::lines($this->render(Metric::CloudflareByStatus, $payload));

        $this->assertSame(['2xx', '3xx', '4xx', '5xx', 'Other'], array_keys($lines));
        $this->assertStringStartsWith('223', $lines['2xx']);
    }

    public function testBreakdownKeepsFiveAndOther(): void
    {
        $payload = self::fixture();
        $payload['traffic']['window']['by_host'] = ['a' => 70, 'b' => 60, 'c' => 50, 'd' => 40, 'e' => 30, 'f' => 20, 'g' => 10];

        $lines = self::lines($this->render(Metric::CloudflareByHost, $payload));

        $this->assertCount(6, $lines);
        $this->assertSame('Other', array_key_last($lines));
        $this->assertStringStartsWith('30', $lines['Other']);
    }

    public function testCountriesAreNamed(): void
    {
        $lines = self::lines($this->render(Metric::CloudflareByCountry, self::fixture()));

        $this->assertArrayHasKey('🇫🇷 France', $lines);
        $this->assertStringStartsWith('192', $lines['🇫🇷 France']);
    }

    public function testSecurityBreakdownsIgnoreRequestsWithoutAnEvent(): void
    {
        $payload = self::fixture();
        $payload['traffic']['window']['security'] = [
            'by_action' => ['unknown' => 900, 'skip' => 171, 'managed_challenge' => 7, 'block' => 31],
            'by_source' => ['unknown' => 900, 'firewallCustom' => 178, 'firewallManaged' => 31, 'brandNewSource' => 2],
        ];

        $actions = self::lines($this->render(Metric::CloudflareSecurityByAction, $payload));
        $this->assertSame(['Block', 'Managed challenge', 'Skip'], array_keys($actions));

        $sources = self::lines($this->render(Metric::CloudflareSecurityBySource, $payload));
        $this->assertSame(['Custom rules', 'Managed rules', 'brandNewSource'], array_keys($sources));

        $this->assertSame('Custom rules', self::lines($this->render(Metric::CloudflareSecurity, $payload))['Top source']);
    }

    public function testAQuietSecurityWindowSaysSo(): void
    {
        $payload = self::fixture();
        $payload['traffic']['window']['security'] = ['by_action' => ['unknown' => 238], 'by_source' => ['unknown' => 238]];

        $this->assertStringContainsString('No security events', $this->render(Metric::CloudflareSecurityByAction, $payload));
        $this->assertStringContainsString('No security events', $this->render(Metric::CloudflareSecurityBySource, $payload));
    }

    public function testEveryBreakdownRendersBars(): void
    {
        foreach ([Metric::CloudflareByCacheStatus, Metric::CloudflareByHost, Metric::CloudflareSecurityByAction, Metric::CloudflareSecurityBySource] as $metric) {
            $html = $this->render($metric, self::fixture());
            $this->assertGreaterThan(0, new Crawler($html)->filter('.progress-bar')->count(), $metric->value);
        }
    }

    public function testAMissingTrafficBlockShowsItsReason(): void
    {
        $payload = self::fixture();
        unset($payload['traffic']);
        $payload['unavailable'] = ['traffic' => 'rate_limited'];

        $this->assertStringContainsString('rate limit reached', $this->render(Metric::CloudflareRequests, $payload));
        $this->assertStringContainsString('rate limit reached', $this->render(Metric::CloudflareByCountry, $payload));
        $this->assertStringContainsString('rate limit reached', $this->render(Metric::CloudflareCacheHitRatio, $payload));
    }

    public function testTodayCachedPercent(): void
    {
        $html = $this->render(Metric::CloudflareToday, self::fixture());

        $this->assertStringContainsString('12 994', $html);
        $this->assertStringContainsString('1.5 %', $html);
        $this->assertStringContainsString('197', $html);
    }

    public function testTodayBandwidthUnitIsMarkupNotText(): void
    {
        $html = $this->render(Metric::CloudflareToday, self::fixture());

        $this->assertStringContainsString('<span class="unit">MiB</span>', $html);
        $this->assertStringNotContainsString('&lt;span', $html);
    }

    public function testTheLineVariantsDeclareTheirSeries(): void
    {
        $dto = $this->dto(Metric::CloudflareRequests, Renderer::Line, self::fixture());
        $this->assertInstanceOf(TimeSerieDto::class, $dto);
        $this->assertSame('cloudflare', $dto->measurement);
        $this->assertSame(['requests', 'origin_requests'], array_keys($dto->fields));
        $this->assertSame(60, $dto->minimumWindow);

        $dto = $this->dto(Metric::CloudflareCacheHitRatio, Renderer::Line, self::fixture());
        $this->assertInstanceOf(TimeSerieDto::class, $dto);
        $this->assertSame(['cache_hit_percent'], array_keys($dto->fields));

        $dto = $this->dto(Metric::CloudflareOriginResponseTime, Renderer::Line, self::fixture());
        $this->assertInstanceOf(TimeSerieDto::class, $dto);
        $this->assertSame(['origin_response_ms_p50', 'origin_response_ms_p95'], array_keys($dto->fields));
    }
}
