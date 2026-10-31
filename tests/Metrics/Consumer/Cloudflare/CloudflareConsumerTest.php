<?php

declare(strict_types=1);

namespace App\Tests\Metrics\Consumer\Cloudflare;

use App\Bridge\InfluxDb\TimestampedPoint;
use App\Entity\Project;
use App\Metrics\Consumer\Cloudflare\CloudflareConsumer;
use App\Metrics\Consumer\Consumer;
use App\Metrics\Dto\Bag\Cloudflare\CloudflareBag;
use App\Metrics\Dto\MetricBagDto;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;

final class CloudflareConsumerTest extends TestCase
{
    public function testPointsAreTimestampedAtTheCloudflareMinuteWithoutTags(): void
    {
        $points = new CloudflareConsumer()->getInfluxPoints($this->bag(self::fixture()));

        $this->assertCount(1, $points);
        $this->assertInstanceOf(TimestampedPoint::class, $points[0]);

        $line = $points[0]->toLineProtocol();
        $this->assertStringStartsWith('cloudflare ', $line);
        $this->assertStringEndsWith(' 1761917460', $line);
        $this->assertStringContainsString('requests=13i', $line);
        $this->assertStringContainsString('uncached_requests=13i', $line);
        $this->assertStringContainsString('origin_response_ms_p95=56', $line);
        $this->assertStringContainsString('status_4xx_percent=0', $line);
    }

    public function testNullOriginValuesAreNotWritten(): void
    {
        $payload = self::fixture();
        $payload['traffic']['minutes'] = [[
            'time' => 1761917520, 'requests' => 0, 'cached_requests' => 0, 'bytes' => 0, 'cached_bytes' => 0,
            'page_views' => 0, 'visits' => 0, 'status_4xx_ratio' => 0.25, 'status_5xx_ratio' => 0.0,
            'origin_requests' => null, 'origin_response_ms_avg' => null, 'origin_response_ms_p50' => null, 'origin_response_ms_p95' => null,
        ]];

        $line = new CloudflareConsumer()->getInfluxPoints($this->bag($payload))[0]->toLineProtocol();

        $this->assertStringNotContainsString('origin_response_ms_p50', $line);
        $this->assertStringNotContainsString('origin_requests', $line);
        $this->assertStringNotContainsString('cache_hit_percent', $line);
        $this->assertStringContainsString('status_4xx_percent=25', $line);
    }

    public function testTheSameMinuteTwiceGivesIdenticalPoints(): void
    {
        $consumer = new CloudflareConsumer();

        $first = $consumer->getInfluxPoints($this->bag(self::fixture()))[0]->toLineProtocol();
        $second = $consumer->getInfluxPoints($this->bag(self::fixture()))[0]->toLineProtocol();

        $this->assertSame($first, $second);
    }

    public function testMinutesAreNotCached(): void
    {
        $cached = new CloudflareConsumer()->getMetricsToCache($this->bag(self::fixture()));

        $this->assertArrayNotHasKey('minutes', $cached['traffic']);
        $this->assertSame(238, $cached['traffic']['window']['requests']);
        $this->assertSame('jmonitor.io', $cached['zone']['name']);
        $this->assertSame([], $cached['unavailable']);
    }

    public function testConstraintsAcceptASkippedPayload(): void
    {
        $validator = Validation::createValidator();
        $constraints = new CloudflareConsumer()->getConstraints(1);

        $this->assertCount(0, $validator->validate([], $constraints));
        $this->assertCount(0, $validator->validate(['unavailable' => []], $constraints));
        $this->assertCount(0, $validator->validate(self::fixture(), $constraints));
        $this->assertCount(1, $validator->validate(['zone' => 'x'], $constraints));
    }

    public function testAMinuteWithoutTimeIsSkipped(): void
    {
        $payload = self::fixture();
        $payload['traffic']['minutes'][] = ['requests' => 4];

        $this->assertCount(1, new CloudflareConsumer()->getInfluxPoints($this->bag($payload)));
    }

    /**
     * @return array<string, mixed>
     */
    private static function fixture(): array
    {
        return json_decode((string) file_get_contents(__DIR__ . '/fixtures/payload_v1.json'), true, flags: JSON_THROW_ON_ERROR);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function bag(array $payload): CloudflareBag
    {
        $bag = MetricBagDto::create($this->createMock(Project::class), Consumer::CLOUDFLARE, 1, $payload, new \DateTimeImmutable(), false);
        $this->assertInstanceOf(CloudflareBag::class, $bag);

        return $bag;
    }
}
