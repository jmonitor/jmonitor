<?php

declare(strict_types=1);

namespace App\Metrics\Dto\Bag\Cloudflare;

/**
 * P75 thresholds published by Google: below the first is good, below the second needs improvement, above is poor.
 */
enum WebVital: string
{
    case Lcp = 'lcp';
    case Inp = 'inp';
    case Cls = 'cls';
    case Ttfb = 'ttfb';

    public function label(): string
    {
        return strtoupper($this->value);
    }

    public function unit(): string
    {
        return $this === self::Cls ? '' : 'ms';
    }

    /**
     * @return array{float, float}
     */
    public function thresholds(): array
    {
        return match ($this) {
            self::Lcp => [2500.0, 4000.0],
            self::Inp => [200.0, 500.0],
            self::Cls => [0.1, 0.25],
            self::Ttfb => [800.0, 1800.0],
        };
    }

    public function rate(?float $value): ?VitalRating
    {
        if ($value === null) {
            return null;
        }

        [$good, $poor] = $this->thresholds();

        return match (true) {
            $value < $good => VitalRating::Good,
            $value < $poor => VitalRating::NeedsImprovement,
            default => VitalRating::Poor,
        };
    }
}
