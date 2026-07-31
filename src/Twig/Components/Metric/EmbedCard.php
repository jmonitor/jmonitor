<?php

declare(strict_types=1);

namespace App\Twig\Components\Metric;

use App\Metrics\Dto\EmbedDto;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
class EmbedCard
{
    public EmbedDto $embed;
    public bool $public = false;
    public bool $preview = false;

    /** Route serving the content-only fragment; set only when the card refreshes in place. */
    public ?string $contentUrl = null;
}
