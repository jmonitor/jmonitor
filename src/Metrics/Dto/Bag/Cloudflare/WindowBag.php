<?php

declare(strict_types=1);

namespace App\Metrics\Dto\Bag\Cloudflare;

use App\Metrics\Dto\Bag;

class WindowBag extends Bag
{
    private const array CACHED_STATUSES = ['hit', 'revalidated', 'stale', 'updating'];

    public int $from {
        get => $this->getInt('from', 0) ?? 0;
    }

    public int $to {
        get => $this->getInt('to', 0) ?? 0;
    }

    public int $spanMinutes {
        get => intdiv(max(0, $this->to - $this->from), 60);
    }

    public int $requests {
        get => $this->getInt('requests', 0) ?? 0;
    }

    public bool $sampled {
        get => $this->getBool('sampled', false) ?? false;
    }

    /** @var array<string, int> */
    public array $byCacheStatus {
        get => $this->counts('by_cache_status');
    }

    /** @var array<string, int> */
    public array $byStatus {
        get => $this->counts('by_status');
    }

    /** @var array<string, int> */
    public array $byCountry {
        get => $this->counts('by_country');
    }

    /** @var array<string, int> */
    public array $byHost {
        get => $this->counts('by_host');
    }

    /** @var array<string, int> */
    public array $securityByAction {
        get => $this->securityCounts('by_action');
    }

    /** @var array<string, int> */
    public array $securityBySource {
        get => $this->securityCounts('by_source');
    }

    public function cacheHitRatio(): ?float
    {
        if ($this->requests === 0) {
            return null;
        }

        $cached = 0;
        foreach (self::CACHED_STATUSES as $status) {
            $cached += $this->byCacheStatus[$status] ?? 0;
        }

        return $cached / $this->requests;
    }

    /**
     * @return array<string, int> 2xx, 3xx, 4xx, 5xx and other, always all present
     */
    public function byStatusClass(): array
    {
        $classes = ['2xx' => 0, '3xx' => 0, '4xx' => 0, '5xx' => 0, 'other' => 0];

        foreach ($this->byStatus as $status => $count) {
            $class = intdiv((int) $status, 100) . 'xx';
            $classes[isset($classes[$class]) ? $class : 'other'] += $count;
        }

        return $classes;
    }

    public function mitigated(): int
    {
        return array_sum($this->mitigations());
    }

    /**
     * @return array<string, int> the actions that stopped or challenged the request
     */
    public function mitigations(): array
    {
        return array_filter($this->securityByAction, fn(string $action): bool => SecurityActionKind::of($action)->isMitigation(), ARRAY_FILTER_USE_KEY);
    }

    public function securityEvents(SecurityActionKind $kind): int
    {
        return array_sum(array_filter($this->securityByAction, fn(string $action): bool => SecurityActionKind::of($action) === $kind, ARRAY_FILTER_USE_KEY));
    }

    public function requestsPerMinute(): ?float
    {
        $seconds = $this->to - $this->from;

        return $seconds <= 0 ? null : $this->requests / ($seconds / 60);
    }

    /**
     * The n largest entries, the rest folded into "other".
     *
     * @param array<string, int> $counts
     * @return array<string, int>
     */
    public static function top(array $counts, int $n = 5): array
    {
        arsort($counts);
        $top = array_slice($counts, 0, $n, true);
        $other = array_sum($counts) - array_sum($top);

        if ($other > 0) {
            $top['other'] = ($top['other'] ?? 0) + $other;
        }

        return $top;
    }

    /**
     * @return array<string, int>
     */
    private function counts(string $key): array
    {
        return is_array($this->get($key)) ? self::toCounts($this->all($key)) : [];
    }

    /**
     * Cloudflare files the requests no security feature acted on under "unknown": they are not events.
     *
     * @return array<string, int>
     */
    private function securityCounts(string $key): array
    {
        $security = is_array($this->get('security')) ? $this->all('security') : [];
        $counts = is_array($security[$key] ?? null) ? self::toCounts($security[$key]) : [];
        unset($counts['unknown']);

        return $counts;
    }

    /**
     * @param array<array-key, mixed> $values
     * @return array<string, int>
     */
    private static function toCounts(array $values): array
    {
        $counts = [];
        foreach ($values as $label => $count) {
            $counts[(string) $label] = (int) $count;
        }

        return $counts;
    }
}
