<?php

declare(strict_types=1);

namespace App\Metrics\Renderer\Configurator;

use App\Metrics\Renderer\Dto\AbstractDto;
use App\Metrics\Renderer\Dto\GaugeDto;
use App\Metrics\Renderer\Options\GaugeRendererOptions;
use App\Metrics\Renderer\Options\RendererOptionsInterface;

/**
 * Automatically sets the gauge color based on the color of the associated badge.
 */
class BadgeColorConfigurator implements MetricRendererOptionsConfiguratorInterface
{
    public function supports(RendererOptionsInterface $options, AbstractDto $dto): bool
    {
        return $dto instanceof GaugeDto && $dto->badge !== null;
    }

    public function configure(RendererOptionsInterface $options, AbstractDto $dto): void
    {
        assert($options instanceof GaugeRendererOptions);

        $options->chartConfig->setColor($dto->badge->style->asGaugeColor());
    }
}
