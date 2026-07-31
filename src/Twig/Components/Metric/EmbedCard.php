<?php

declare(strict_types=1);

namespace App\Twig\Components\Metric;

use App\Metrics\Dto\EmbedDto;
use App\Metrics\MetricDtoProvider;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;
use Symfony\UX\TwigComponent\Attribute\ExposeInTemplate;

#[AsTwigComponent]
class EmbedCard
{
    public EmbedDto $embed;
    public bool $public = false;
    public bool $preview = false;

    /** Route serving the content-only fragment; set only when the card refreshes in place. */
    public ?string $contentUrl = null;

    private readonly MetricDtoProvider $dtoProvider;

    public function __construct(MetricDtoProvider $dtoProvider)
    {
        $this->dtoProvider = $dtoProvider;
    }

    #[ExposeInTemplate]
    public function getDto()
    {
        return $this->dtoProvider->getDto($this->embed->metric, $this->embed->renderer);
    }
}
