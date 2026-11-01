<?php

declare(strict_types=1);

namespace App\Metrics\Dto\Bag\Cloudflare;

enum VitalRating: string
{
    case Good = 'good';
    case NeedsImprovement = 'needs_improvement';
    case Poor = 'poor';

    public function label(): string
    {
        return match ($this) {
            self::Good => 'Good',
            self::NeedsImprovement => 'Needs improvement',
            self::Poor => 'Poor',
        };
    }

    public function cssClass(): string
    {
        return match ($this) {
            self::Good => 'text-success',
            self::NeedsImprovement => 'text-warning',
            self::Poor => 'text-danger',
        };
    }
}
