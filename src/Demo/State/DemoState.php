<?php

declare(strict_types=1);

namespace App\Demo\State;

use Psr\Cache\CacheItemPoolInterface;

/**
 * Cache-backed replacement for App\Dev\SumTrait. Holds the demo worker's
 * evolving state (cumulative counters + current value of each random walk) so
 * that values stay coherent across worker restarts.
 */
class DemoState
{
    private const string CACHE_KEY = 'demo.state';
    private const int TTL = 2592000; // 30 days

    /** @var array<string, int|float> */
    private array $state = [];
    private bool $loaded = false;

    public function __construct(private readonly CacheItemPoolInterface $cache) {}

    /**
     * Monotonic cumulative counter (simulates SHOW STATUS / pg_stat counters).
     */
    public function counter(string $key, int|float $increment): int|float
    {
        $this->load();
        $increment = max(0, $increment);
        $this->state[$key] = ($this->state[$key] ?? 0) + $increment;

        return $this->state[$key];
    }

    /**
     * Bounded mean-reverting random walk (simulates instantaneous gauges).
     */
    public function walk(string $key, float $min, float $max, float $volatility): float
    {
        $this->load();
        $mid = ($min + $max) / 2;
        $current = (float) ($this->state[$key] ?? $mid);

        $range = $max - $min;
        $step = (mt_rand(-1000, 1000) / 1000) * $volatility * $range;
        $pull = ($mid - $current) * 0.1; // gentle reversion toward the centre

        $next = max($min, min($max, $current + $step + $pull));
        $this->state[$key] = $next;

        return $next;
    }

    /**
     * Daily traffic curve: ~0.30 at 04:00, ~1.00 at 16:00. Shared by all
     * generators so components "breathe" together.
     */
    public function seasonality(?int $hour = null): float
    {
        $hour ??= (int) date('G');
        $phase = cos(($hour - 16) / 24 * 2 * M_PI);

        return 0.65 + 0.35 * $phase;
    }

    public function persist(): void
    {
        $this->load();
        $item = $this->cache->getItem(self::CACHE_KEY);
        $item->set($this->state);
        $item->expiresAfter(self::TTL);
        $this->cache->save($item);
    }

    private function load(): void
    {
        if ($this->loaded) {
            return;
        }

        $stored = $this->cache->getItem(self::CACHE_KEY)->get();
        $this->state = is_array($stored) ? $stored : [];
        $this->loaded = true;
    }
}
