<?php

declare(strict_types=1);

namespace App\Metrics\Metric\Cloudflare;

use App\Chart\Units\Bytes;

/**
 * A month-to-date counter against its Free plan allowance, ready for the quota bar.
 */
final readonly class Quota
{
    private const int WARNING_PERCENT = 80;

    private function __construct(
        public string $label,
        public string $used,
        public string $allowed,
        public float $percent,
    ) {}

    public static function count(string $label, int $used, int $allowed): self
    {
        return new self($label, self::number($used), self::number($allowed), self::percent($used, $allowed));
    }

    public static function bytes(string $label, int $used, int $allowed): self
    {
        return new self($label, self::bytesText($used), self::bytesText($allowed), self::percent($used, $allowed));
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

    private static function number(int $value): string
    {
        return number_format($value, 0, '.', ' ');
    }

    private static function bytesText(int $value): string
    {
        $bytes = Bytes::parse($value);
        $quantity = $bytes->getFinalValue();

        return ($quantity === floor($quantity) ? sprintf('%d', $quantity) : sprintf('%.2f', $quantity)) . ' ' . $bytes->getUnit();
    }
}
