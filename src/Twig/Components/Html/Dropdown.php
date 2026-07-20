<?php

namespace App\Twig\Components\Html;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
class Dropdown
{
    public bool $disabled = false;
}
