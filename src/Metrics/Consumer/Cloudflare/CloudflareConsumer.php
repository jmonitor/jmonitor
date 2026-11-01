<?php

declare(strict_types=1);

namespace App\Metrics\Consumer\Cloudflare;

use App\Bridge\InfluxDb\TimestampedPoint;
use App\Metrics\Consumer\Consumer;
use App\Metrics\Consumer\ConsumerInterface;
use App\Metrics\Dto\Bag\Cloudflare\CloudflareBag;
use App\Metrics\Dto\Bag\Cloudflare\TrafficMinute;
use App\Metrics\Dto\MetricBagDto;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\Collection;

/**
 * Payload version 1 of the jmonitor/collector Cloudflare collector.
 */
#[AsTaggedItem(Consumer::CLOUDFLARE->value)]
class CloudflareConsumer implements ConsumerInterface
{
    public const string MEASUREMENT = 'cloudflare';

    private const array BLOCKS = ['zone', 'traffic', 'today', 'web_analytics', 'r2', 'images', 'unavailable'];

    public function normalizeBag(MetricBagDto $bag): MetricBagDto
    {
        return $bag;
    }

    public function getMetricsToCache(MetricBagDto $bag): array
    {
        return $bag->all();
    }

    /**
     * @param CloudflareBag $bag
     */
    public function getInfluxPoints(MetricBagDto $bag): array
    {
        $points = [];

        foreach ($bag->traffic === null ? [] : $bag->traffic->minutes as $minute) {
            $points[] = new TimestampedPoint(self::MEASUREMENT, $minute->time, $this->fields($minute));
        }

        return $points;
    }

    public function getConstraints(int $version): Constraint|array
    {
        return new Collection(
            fields: array_fill_keys(self::BLOCKS, new Assert\Type('array')),
            allowExtraFields: true,
            allowMissingFields: true,
        );
    }

    /**
     * @return array<string, int|float>
     */
    private function fields(TrafficMinute $minute): array
    {
        $ratio = $minute->cacheHitRatio();
        $served = $minute->requests > 0;

        return array_filter([
            'requests' => $minute->requests,
            'cached_requests' => $minute->cachedRequests,
            'uncached_requests' => $minute->uncachedRequests(),
            'cache_hit_percent' => $ratio === null ? null : $ratio * 100,
            'bytes' => $minute->bytes,
            'cached_bytes' => $minute->cachedBytes,
            'page_views' => $minute->pageViews,
            'visits' => $minute->visits,
            'status_4xx_percent' => $served ? $minute->status4xxRatio * 100 : null,
            'status_5xx_percent' => $served ? $minute->status5xxRatio * 100 : null,
            'origin_requests' => $minute->originRequests,
            'origin_response_ms_avg' => $minute->originResponseMsAvg,
            'origin_response_ms_p50' => $minute->originResponseMsP50,
            'origin_response_ms_p95' => $minute->originResponseMsP95,
        ], fn($value): bool => $value !== null);
    }
}
