<?php

declare(strict_types=1);

namespace App\Metrics\Renderer\Dto\Embed;

use App\Chart\TimeRange;
use App\Metrics\Dto\EmbedDto;
use App\Metrics\Renderer\Options\GaugeRendererOptions;
use App\Metrics\Renderer\Options\TimeSeriesRendererOptions;
use App\Metrics\Renderer\Options\RendererOptionsInterface;
use App\Metrics\Renderer\Options\RendererOptionsBuilderInterface;

/**
 * Stores and applies an Embed's options onto the underlying Renderer.
 * This is not a service, it is a DTO.
 */
class EmbedRendererOptionsBuilder implements RendererOptionsBuilderInterface
{
    private ?TimeRange $range = null;
    private ?float $aspectRatio = null;
    private ?bool $displayHelp = null;

    public static function fromEmbedDto(EmbedDto $embedDto): self
    {
        $aspectRatio = $embedDto->chartConfig['aspectRatio'] ?? null;

        $builder = new self();
        $builder
            ->range($embedDto->range)
            ->aspectRatio(is_numeric($aspectRatio) ? (float) $aspectRatio : null)
            ->displayHelp(false);

        return $builder;
    }

    public function range(?TimeRange $range): static
    {
        $this->range = $range;

        return $this;
    }

    public function aspectRatio(?float $ratio): static
    {
        $this->aspectRatio = $ratio;

        return $this;
    }

    public function displayHelp(?bool $display): static
    {
        $this->displayHelp = $display;

        return $this;
    }

    public function applyTo(RendererOptionsInterface $options): void
    {
        if ($options instanceof TimeSeriesRendererOptions) {
            $options->chartConfig->setRange($this->range ?? $options->chartConfig->range);
            $options->chartConfig->setAspectRatio($this->aspectRatio ?? $options->chartConfig->aspectRatio);
        }

        if ($options instanceof GaugeRendererOptions) {
            $options->setDisplayHelp($this->displayHelp ?? $options->displayHelp);
            $options->chartConfig->setAspectRatio($this->aspectRatio ?? $options->chartConfig->aspectRatio);
        }
    }
}
