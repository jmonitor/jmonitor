<?php

declare(strict_types=1);

namespace App\Twig\Components\Metric\Help\Section;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
class Definition
{
    public array $paragraphes = [];
}
