<?php

declare(strict_types=1);

namespace App\Metrics\Renderer\Model\Badge;

class Badge
{
    public private(set) ?string $label;
    public private(set) BadgeStyle $style;
    public private(set) string|bool|null $icon = null;

    public function __construct(BadgeStyle $style, ?string $label = null, string|bool|null $icon = true)
    {
        $this->label = $label;
        $this->style = $style;
        $this->icon = $icon;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function getStyle(): BadgeStyle
    {
        return $this->style;
    }

    public function getIcon(): string|bool|null
    {
        return $this->icon;
    }
}
