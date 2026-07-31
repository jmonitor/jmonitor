<?php

declare(strict_types=1);

namespace App\Metrics\Dto\Embed;

use App\Form\Embed\GaugeEmbedOptionsType;
use App\Form\Embed\TimeSeriesEmbedOptionsType;
use App\Metrics\Renderer;

/**
 * The only place a renderer is mapped to the options it supports.
 * Static because EmbedDto::fromArray() is static and the Embed entity has no container.
 * It knowingly reaches into the form layer: one file answering "what does this renderer
 * support" is worth more here than a strict layer split.
 */
final class EmbedOptionsFactory
{
    /** @param array<string, mixed> $data */
    public static function hydrate(?Renderer $renderer, array $data): ?ChartEmbedOptionsInterface
    {
        $class = self::optionsClass($renderer);

        return $class ? $class::fromArray($data) : null;
    }

    public static function createEmpty(?Renderer $renderer): ?ChartEmbedOptionsInterface
    {
        return self::hydrate($renderer, []);
    }

    /** @return class-string|null */
    public static function formType(?Renderer $renderer): ?string
    {
        return match ($renderer) {
            Renderer::Line, Renderer::Bar => TimeSeriesEmbedOptionsType::class,
            Renderer::Gauge => GaugeEmbedOptionsType::class,
            default => null,
        };
    }

    /** @return class-string<ChartEmbedOptionsInterface>|null */
    private static function optionsClass(?Renderer $renderer): ?string
    {
        return match ($renderer) {
            Renderer::Line, Renderer::Bar => TimeSeriesEmbedOptions::class,
            Renderer::Gauge => GaugeEmbedOptions::class,
            default => null,
        };
    }
}
