<?php

declare(strict_types=1);

namespace App\Metrics\Renderer;

use App\Metrics\Metric\MetricInterface;
use App\Metrics\Renderer\Options\RendererOptionsInterface;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('app.metric.renderer')]
interface MetricRendererInterface
{
    public function render($dto, RendererOptionsInterface $options): string;

    public function createDto(MetricInterface $metric, array $dtoOptions);

    /**
     * Creates the options object for this Renderer.
     */
    public function createRendererOptions(): RendererOptionsInterface;
}
