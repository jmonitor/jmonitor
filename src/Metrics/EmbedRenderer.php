<?php

declare(strict_types=1);

namespace App\Metrics;

use App\Metrics\Dto\EmbedDto;
use Symfony\Component\OptionsResolver\Exception\ExceptionInterface as OptionsResolverException;
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
        try {
            return $this->metricRenderer->render($embed->metric, $embed->renderer, $embed->chart, $embed->metricOptions);
        } catch (OptionsResolverException) {
            // Metric options come from a stored config or from the sidebar's query string: a
            // crafted set, or one the metric no longer accepts, degrades to the card's error
            // state instead of failing the whole page.
            return $this->twig->render('dash/project/metrics/error/_rendering_error.html.twig');
        }
    }
}
