<?php

declare(strict_types=1);

namespace App\Metrics\Renderer;

use App\Metrics\Renderer\Configurator\MetricRendererOptionsConfiguratorInterface;
use App\Metrics\Renderer\Dto\AbstractDto;
use App\Metrics\Renderer\Options\RendererOptionsInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

/**
 * Returns the renderer's default "options" when a metric is rendered without specific options.
 * The options must be created for a given DTO (this metric) and for a given Renderer, of course.
 *
 * There can be several configurators: for example we start with the "default" options,
 * then there can be an override for the logged-in user, etc.
 */
readonly class MetricRendererOptionsFactory
{
    /**
     * @param iterable<MetricRendererOptionsConfiguratorInterface> $configurators
     */
    public function __construct(
        #[AutowireIterator(tag: 'app.metric.renderer.options_configurator')]
        private iterable $configurators,
    ) {}

    public function createOptions(MetricRendererInterface $renderer, AbstractDto $dto): RendererOptionsInterface
    {
        // create the right object for this Renderer (the available options depend on the renderer)
        $options = $renderer->createRendererOptions();

        // and pass it to every configurator
        foreach ($this->configurators as $configurator) {
            if ($configurator->supports($options, $dto)) {
                $configurator->configure($options, $dto);
            }
        }

        return $options;
    }
}
