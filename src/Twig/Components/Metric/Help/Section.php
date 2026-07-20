<?php

declare(strict_types=1);

namespace App\Twig\Components\Metric\Help;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
class Section
{
    public ?string $title = null;
    public ?string $icon = null;
}
