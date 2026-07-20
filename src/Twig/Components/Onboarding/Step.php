<?php

namespace App\Twig\Components\Onboarding;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
class Step
{
    public bool $enabled;
}
