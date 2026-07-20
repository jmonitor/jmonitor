<?php

namespace App\Twig\Components\Html\Badge;

use App\Metrics\Renderer\Model\Badge\Badge as BadgeDto;
use App\Metrics\Renderer\Model\Badge\BadgeStyle;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;
use Symfony\UX\TwigComponent\Attribute\ExposeInTemplate;

#[AsTwigComponent]
class Badge
{
    public ?BadgeDto $badge = null;
    public ?string $icon = null;
    public ?BadgeStyle $style = null;
    public ?string $cssClass = null;

    #[ExposeInTemplate]
    public function getCssClass(): ?string
    {
        return
            $this->badge?->getStyle()->getCssClass()
            ?? $this->style?->getCssClass()
            ?? $this->cssClass;
    }

    #[ExposeInTemplate]
    public function getIcon(): ?string
    {
        return match (true) {
            is_string($this->badge?->icon) => $this->badge->icon,
            $this->badge?->icon === true => $this->badge->getStyle()->getDefaultIcon(),
            is_string($this->icon) => $this->icon,
            $this->style !== null => $this->style->getDefaultIcon(),
            default => null,
        };
    }

    public function setStyle(string|BadgeStyle $style): void
    {
        if (is_string($style)) {
            $style = BadgeStyle::from($style);
        }

        $this->style = $style;
    }
}
