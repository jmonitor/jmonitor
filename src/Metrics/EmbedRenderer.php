<?php

declare(strict_types=1);

namespace App\Metrics;

use App\Metrics\Dto\EmbedDto;
use App\Metrics\Renderer\Dto\Embed\EmbedRendererOptionsBuilder;
use Twig\Attribute\AsTwigFilter;
use Twig\Environment;

readonly class EmbedRenderer
{
    /** Id of the turbo-frame wrapping the metric content, refreshed in place by the card. */
    public const string CONTENT_FRAME_ID = 'embed-content';

    public function __construct(
        private MetricRenderer $metricRenderer,
        private Environment $twig,
    ) {}

    #[AsTwigFilter('render_embed', isSafe: ['html'])]
    public function render(EmbedDto $embed, bool $public = false, bool $preview = false, ?string $contentUrl = null): string
    {
        return $this->twig->render('dash/embed/_embed.html.twig', [
            'inner' => $this->renderInner($embed),
            'embed' => $embed,
            'public' => $public,
            'preview' => $preview,
            'contentUrl' => $contentUrl,
        ]);
    }

    /** Content-only fragment served to the card's auto-refresh, without the surrounding card. */
    #[AsTwigFilter('render_embed_content', isSafe: ['html'])]
    public function renderContent(EmbedDto $embed): string
    {
        return $this->twig->render('dash/embed/_embed_content.html.twig', [
            'inner' => $this->renderInner($embed),
        ]);
    }

    private function renderInner(EmbedDto $embed): string
    {
        return $this->metricRenderer->render(
            $embed->metric,
            $embed->renderer,
            EmbedRendererOptionsBuilder::fromEmbedDto($embed),
        );
    }
}
