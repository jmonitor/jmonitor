<?php

declare(strict_types=1);

namespace App\Twig\Components\Html;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;
use Symfony\UX\TwigComponent\Attribute\ExposeInTemplate;

#[AsTwigComponent]
class ProgressBar
{
    public int $value = 0;
    public int $max;
    public string $label;
    public ?int $height = null;
    public bool $showPercent = true;

    #[ExposeInTemplate]
    public function getPercent(): int
    {
        return (int) round($this->value / $this->max * 100);
    }
}
