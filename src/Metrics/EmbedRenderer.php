<?php

declare(strict_types=1);

namespace App\Metrics;

use App\Metrics\Dto\EmbedDto;
use App\Metrics\Renderer\Dto\Embed\EmbedRendererOptionsBuilder;
use Twig\Attribute\AsTwigFilter;
use Twig\Environment;

readonly class EmbedRenderer
{
    public function __construct(
        private MetricRenderer $metricRenderer,
        private Environment $twig,
    ) {}

    #[AsTwigFilter('render_embed', isSafe: ['html'])]
    public function render(EmbedDto $embed, bool $public = false): string
    {
        $rendererOptions = EmbedRendererOptionsBuilder::fromEmbedDto($embed);

        $inner = $this->metricRenderer->render(
            $embed->metric,
            $embed->renderer,
            $rendererOptions,
        );

        return $this->twig->render('dash/embed/_embed.html.twig', [
            'inner' => $inner,
            'embed' => $embed,
            'public' => $public,
        ]);
    }
}
