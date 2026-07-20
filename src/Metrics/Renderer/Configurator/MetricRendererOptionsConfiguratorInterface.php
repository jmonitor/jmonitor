<?php

declare(strict_types=1);

namespace App\Metrics\Renderer\Configurator;

use App\Metrics\Renderer\Dto\AbstractDto;
use App\Metrics\Renderer\Options\RendererOptionsInterface;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * Configures the renderer options of a metric.
 */
#[AutoconfigureTag('app.metric.renderer.options_configurator')]
interface MetricRendererOptionsConfiguratorInterface
{
    public function supports(RendererOptionsInterface $options, AbstractDto $dto): bool;

    public function configure(RendererOptionsInterface $options, AbstractDto $dto): void;
}
