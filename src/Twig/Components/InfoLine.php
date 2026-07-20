<?php

declare(strict_types=1);

namespace App\Twig\Components;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;
use Symfony\UX\TwigComponent\Attribute\ExposeInTemplate;

#[AsTwigComponent]
class InfoLine
{
    public ?string $label = null;
    public string|float|bool|null $value = null;
    public ?string $icon = null;
}
