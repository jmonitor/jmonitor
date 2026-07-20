<?php

namespace App\Metrics\LastPush;

use App\Metrics\Renderer\Model\Badge\Badge;
use App\Metrics\Renderer\Model\Badge\BadgeStyle;

enum LastPushStatus
{
    case ACTIVE;
    case LATE;
    case INACTIVE;

    public function description(): string
    {
        return match ($this) {
            self::ACTIVE => 'Metrics are being received.',
            self::LATE => 'No metrics have been received recently.',
            self::INACTIVE => 'Your collector seems inactive.',
        };
    }

    public function badge(): Badge
    {
        return match ($this) {
            self::ACTIVE => new Badge(BadgeStyle::SUCCESS, 'Active'),
            self::LATE => new Badge(BadgeStyle::WARNING, 'Late'),
            self::INACTIVE => new Badge(BadgeStyle::DANGER, 'Inactive'),
        };
    }

    public function icon(): string
    {
        return $this->badge()->getStyle()->getDefaultIcon();
    }

    public function isActive(): bool
    {
        return $this === self::ACTIVE;
    }

    public function isLate(): bool
    {
        return $this === self::LATE;
    }

    public function isInactive(): bool
    {
        return $this === self::INACTIVE;
    }
}
