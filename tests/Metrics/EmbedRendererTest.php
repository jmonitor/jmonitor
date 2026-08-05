<?php

declare(strict_types=1);

namespace App\Tests\Metrics;

use App\Metrics\Dto\Embed\TimeSeriesEmbedOptions;
use App\Metrics\Dto\EmbedDto;
use App\Metrics\EmbedRenderer;
use App\Metrics\Metric;
use App\Metrics\MetricRenderer;
use App\Metrics\Renderer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;
use Twig\Environment;

class EmbedRendererTest extends TestCase
{
    /**
     * Regression: metrics like caddy.req_per_sec exist once per handler and require the
     * option picking one. Dropping it on the way to the renderer made every embed of such a
     * metric fail with "The required option "handler" is missing".
     */
    public function testTheMetricOptionsReachTheMetricRenderer(): void
    {
        $embed = new EmbedDto(
            Metric::CaddyReqPerSec,
            Renderer::Line,
            chart: new TimeSeriesEmbedOptions(),
            metricOptions: ['handler' => 'php'],
        );

        $metricRenderer = $this->createMock(MetricRenderer::class);
        $metricRenderer
            ->expects($this->once())
            ->method('render')
            ->with($embed->metric, $embed->renderer, $embed->chart, ['handler' => 'php'])
            ->willReturn('<div>chart</div>')
        ;

        $twig = $this->createMock(Environment::class);
        $twig
            ->expects($this->once())
            ->method('render')
            ->with('dash/embed/_embed.html.twig', $this->callback(static fn(array $context): bool => $context['inner'] === '<div>chart</div>'))
            ->willReturn('<div>card</div>')
        ;

        $this->assertSame('<div>card</div>', new EmbedRenderer($metricRenderer, $twig)->render($embed, preview: true));
    }

    public function testTheMetricOptionsAlsoReachTheContentOnlyFragment(): void
    {
        $embed = new EmbedDto(Metric::CaddyReqPerSec, Renderer::Line, metricOptions: ['handler' => 'file_server']);

        $metricRenderer = $this->createMock(MetricRenderer::class);
        $metricRenderer
            ->expects($this->once())
            ->method('render')
            ->with($embed->metric, $embed->renderer, $embed->chart, ['handler' => 'file_server'])
            ->willReturn('<div>chart</div>')
        ;

        $twig = $this->createMock(Environment::class);
        $twig->method('render')->willReturn('<div>content</div>');

        $this->assertSame('<div>content</div>', new EmbedRenderer($metricRenderer, $twig)->renderContent($embed));
    }

    /**
     * Options a metric no longer accepts (stored config) or never accepted (crafted query
     * string) must degrade to the card's error state instead of a 500.
     */
    public function testUnacceptableMetricOptionsDegradeToTheErrorCard(): void
    {
        $embed = new EmbedDto(Metric::CaddyReqPerSec, Renderer::Line, metricOptions: ['handler' => 'php']);

        $metricRenderer = $this->createMock(MetricRenderer::class);
        $metricRenderer->method('render')->willThrowException(new MissingOptionsException('The required option "handler" is missing.'));

        $twig = $this->createMock(Environment::class);
        $twig
            ->expects($this->exactly(2))
            ->method('render')
            ->willReturnCallback(static fn(string $template): string => $template === 'dash/project/metrics/error/_rendering_error.html.twig' ? '<div>error</div>' : '<div>card</div>')
        ;

        $this->assertSame('<div>card</div>', new EmbedRenderer($metricRenderer, $twig)->render($embed, preview: true));
    }
}
