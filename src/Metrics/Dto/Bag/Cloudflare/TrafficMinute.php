<?php

declare(strict_types=1);

namespace App\Metrics\Dto\Bag\Cloudflare;

use App\Metrics\Dto\Bag;

final readonly class TrafficMinute
{
    public function __construct(
        public int $time,
        public int $requests,
        public int $cachedRequests,
        public int $bytes,
        public int $cachedBytes,
        public int $pageViews,
        public int $visits,
        public float $status4xxRatio,
        public float $status5xxRatio,
        public ?int $originRequests,
        public ?float $originResponseMsAvg,
        public ?float $originResponseMsP50,
        public ?float $originResponseMsP95,
    ) {}

    /**
     * @param array<string, mixed> $minute
     */
    public static function fromArray(array $minute): ?self
    {
        if (!is_int($minute['time'] ?? null)) {
            return null;
        }

        $bag = new Bag($minute);

        return new self(
            time: $minute['time'],
            requests: $bag->getInt('requests', 0) ?? 0,
            cachedRequests: $bag->getInt('cached_requests', 0) ?? 0,
            bytes: $bag->getInt('bytes', 0) ?? 0,
            cachedBytes: $bag->getInt('cached_bytes', 0) ?? 0,
            pageViews: $bag->getInt('page_views', 0) ?? 0,
            visits: $bag->getInt('visits', 0) ?? 0,
            status4xxRatio: $bag->getFloat('status_4xx_ratio', 0) ?? 0.0,
            status5xxRatio: $bag->getFloat('status_5xx_ratio', 0) ?? 0.0,
            originRequests: $bag->getInt('origin_requests'),
            originResponseMsAvg: $bag->getFloat('origin_response_ms_avg'),
            originResponseMsP50: $bag->getFloat('origin_response_ms_p50'),
            originResponseMsP95: $bag->getFloat('origin_response_ms_p95'),
        );
    }

    public function uncachedRequests(): int
    {
        return max(0, $this->requests - $this->cachedRequests);
    }

    public function cacheHitRatio(): ?float
    {
        return $this->requests === 0 ? null : $this->cachedRequests / $this->requests;
    }
}
