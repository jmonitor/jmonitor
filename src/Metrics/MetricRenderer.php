<?php

declare(strict_types=1);

namespace App\Metrics;

use App\Metrics\Renderer\Error\EmptyDataException;
use App\Metrics\Renderer\Error\RenderingException;
use App\Metrics\Renderer\MetricRendererInterface;
use App\Metrics\Renderer\MetricRendererOptionsFactory;
use App\Metrics\Renderer\Options\RendererOptionsBuilderInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireLocator;
use Twig\Attribute\AsTwigFilter;
use Twig\Environment;

readonly class MetricRenderer
{
    public function __construct(
        #[AutowireLocator('app.metric.renderer')]
        private ContainerInterface $renderers,
        private MetricDtoProvider $metricDtoProvider,
        private MetricRendererOptionsFactory $optionsFactory,
        private Environment $twig,
    ) {}

    public function render(Metric $metric, ?Renderer $renderer = null, ?RendererOptionsBuilderInterface $optionsBuilder = null, array $dtoOptions = []): string
    {
        $renderer ??= $metric->defaultRenderer();
        $dto = $this->metricDtoProvider->getDto($metric, $renderer, $dtoOptions);

        if (!$dto) {
            return $this->twig->render('dash/project/metrics/error/_no_data.html.twig', [
                'mode' => $renderer->getDefaultEmptyDataMode(),
            ]);
        }

        if (!$dto->valueAvailable) {
            return $this->twig->render('dash/project/metrics/error/_no_data.html.twig', [
                'mode' => $dto->emptyDataMode ?? $renderer->getDefaultEmptyDataMode(),
            ]);
        }

        /** @var MetricRendererInterface $rendererService */
        $rendererService = $this->renderers->get($renderer->value);

        $options = $this->optionsFactory->createOptions($rendererService, $dto);
        $optionsBuilder?->applyTo($options);

        try {
            return $rendererService->render($dto, $options);
        } catch (EmptyDataException) {
            return $this->twig->render('dash/project/metrics/error/_no_data.html.twig', [
                'mode' => $dto->emptyDataMode ?? $renderer->getDefaultEmptyDataMode(),
            ]);
        } catch (RenderingException) {
            return $this->twig->render('dash/project/metrics/error/_rendering_error.html.twig');
        }
    }

    /**
     * @internal
     */
    #[AsTwigFilter('render_metric', isSafe: ['html'])]
    public function twigRender(string $metric, ?string $renderer = null, array $dtoOptions = []): string
    {
        return $this->render(Metric::from($metric), $renderer ? Renderer::from($renderer) : null, dtoOptions: $dtoOptions);
    }
}
