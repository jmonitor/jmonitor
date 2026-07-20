<?php

namespace App\Twig\Components\Form;

use Symfony\Component\Form\FormView;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
class InputGroup
{
    public FormView $form;
    public ?string $suffix = null;
}
