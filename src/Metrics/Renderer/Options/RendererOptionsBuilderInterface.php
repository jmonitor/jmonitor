<?php

declare(strict_types=1);

namespace App\Metrics\Renderer\Options;

/**
 * Interface for DTOs that want control over some Renderer options.
 * The DTO can declare as many "helper" properties/functions as needed to store the config values.
 */
interface RendererOptionsBuilderInterface
{
    /**
     * Applies the config values on the existing options (the renderer's default options)
     */
    public function applyTo(RendererOptionsInterface $options): void;
}
