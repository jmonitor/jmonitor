<?php

namespace App\Twig\Components\Form;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
class Submit
{
    public ?string $label = 'Submit';
    public ?string $cancelUrl = null;
}
