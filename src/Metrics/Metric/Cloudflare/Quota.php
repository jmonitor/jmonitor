<?php

declare(strict_types=1);

namespace App\Metrics\Metric\Cloudflare;

/**
 * A month-to-date counter against its Free plan allowance, ready for the quota bar.
 */
final readonly class Quota
{
    private const int WARNING_PERCENT = 80;

    private function __construct(
        public string $label,
        public int $used,
        public int $allowed,
        public bool $inBytes,
        public float $percent,
    ) {}

    public static function count(string $label, int $used, int $allowed): self
    {
        return new self($label, $used, $allowed, false, self::percent($used, $allowed));
    }

    public static function bytes(string $label, int $used, int $allowed): self
    {
        return new self($label, $used, $allowed, true, self::percent($used, $allowed));
    }

    public function barClass(): string
    {
        return match (true) {
            $this->percent >= 100 => 'bg-danger',
            $this->percent >= self::WARNING_PERCENT => 'bg-warning',
            default => '',
        };
    }

    private static function percent(int $used, int $allowed): float
    {
        return $allowed <= 0 ? 0.0 : round($used / $allowed * 100, 1);
    }
}
