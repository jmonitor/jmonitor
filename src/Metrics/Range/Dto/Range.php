<?php

declare(strict_types=1);

namespace App\Metrics\Range\Dto;

use App\Metrics\Renderer\Model\Badge\Badge;
use App\Metrics\Renderer\Model\Badge\BadgeStyle;

/**
 * A value range and how to interpret it for a given metric (e.g. 0 to 70 (%) is healthy).
 * Used for badges, help texts, ...
 */
class Range
{
    public private(set) int $min;
    public private(set) int $max;
    public private(set) BadgeStyle $style;
    public private(set) string $label; // typically the label shown in the badge
    public private(set) string $meaning; // a few words shown in the help
    public private(set) ?string $notes; // extra note about the "current value" in the help

    public function __construct(int $min, int $max, BadgeStyle $style, string $label, ?string $meaning, ?string $notes = null)
    {
        $this->min = $min;
        $this->max = $max;
        $this->style = $style;
        $this->label = $label;
        $this->meaning = $meaning;
        $this->notes = $notes;
    }

    public function badge(): Badge
    {
        return new Badge($this->style, $this->label);
    }
}
