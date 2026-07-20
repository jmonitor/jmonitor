<?php

declare(strict_types=1);

namespace App\Twig\Components;

use App\Range\RangeContext;
use App\Range\Dto\RangeDto;
use Symfony\Component\Form\FormView;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;
use Symfony\UX\TwigComponent\Attribute\ExposeInTemplate;

#[AsTwigComponent]
readonly class RangeForm
{
    public function __construct(
        private RangeContext $rangeContext,
    ) {}

    #[ExposeInTemplate]
    public function getForm(): FormView
    {
        return $this->rangeContext->getForm()->createView();
    }

    #[ExposeInTemplate]
    public function getFilter(): RangeDto
    {
        return $this->rangeContext->getRangeDto();
    }
}
