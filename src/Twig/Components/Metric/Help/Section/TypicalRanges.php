<?php

declare(strict_types=1);

namespace App\Twig\Components\Metric\Help\Section;

use App\Metrics\Metric;
use App\Metrics\Range\Dto\Ranges;
use App\Metrics\Range\TypicalRangesProvider;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;
use Symfony\UX\TwigComponent\Attribute\ExposeInTemplate;

#[AsTwigComponent]
class TypicalRanges
{
    public Metric $metric;
    private readonly TypicalRangesProvider $rangesProvider;

    public function __construct(TypicalRangesProvider $rangesProvider)
    {
        $this->rangesProvider = $rangesProvider;
    }

    #[ExposeInTemplate]
    public function getRanges(): ?Ranges
    {
        return $this->rangesProvider->get($this->metric);
    }
}
