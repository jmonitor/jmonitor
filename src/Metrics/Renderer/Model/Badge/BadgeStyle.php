<?php

declare(strict_types=1);

namespace App\Metrics\Renderer\Model\Badge;

use App\Chart\Gauge\Color;

enum BadgeStyle: string
{
    case SUCCESS = 'success';
    case WARNING = 'warning';
    case DANGER = 'danger';
    case INFO = 'info';
    case NEUTRAL = 'neutral';

    public function getDefaultIcon(): ?string
    {
        return match ($this) {
            self::SUCCESS => 'material-symbols:check-circle-outline',
            self::WARNING => 'flowbite:exclamation-circle-outline', //'bi:exclamation-circle',
            self::DANGER => 'material-symbols:x-circle-outline',
            self::INFO => null,
            default => null,
        };
    }

    public function getCssClass(): string
    {
        return match ($this) {
            self::SUCCESS => 'badge-success',
            self::WARNING => 'badge-warning',
            self::DANGER => 'badge-danger',
            self::INFO => 'text-bg-info',
            self::NEUTRAL => 'badge-light',
        };
    }

    public function asGaugeColor(): Color
    {
        return match ($this) {
            self::SUCCESS => Color::HEALTHY,
            self::WARNING => Color::WARNING,
            self::DANGER => Color::DANGER,
            self::INFO => Color::INFO,
            default => throw new \LogicException(sprintf('No gauge color is defined for the "%s" badge style.', $this->value)),
        };
    }
}
