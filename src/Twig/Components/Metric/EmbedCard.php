<?php

declare(strict_types=1);

namespace App\Twig\Components\Metric;

use App\Metrics\Dto\EmbedDto;
use App\Metrics\MetricDtoProvider;
use DateTimeImmutable;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;
use Symfony\UX\TwigComponent\Attribute\ExposeInTemplate;

#[AsTwigComponent]
class EmbedCard
{
    public EmbedDto $embed;

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

    #[ExposeInTemplate]
    public function getLastUpdate(): DateTimeImmutable
    {
        // stub: embeds don't track their real last-update time yet
        return new DateTimeImmutable('-5 seconds');
    }
}
