<?php

declare(strict_types=1);

namespace App\Metrics\Dto\Embed;

use App\Chart\TimeRange;
use App\Metrics\Renderer\Options\RendererOptionsInterface;
use App\Metrics\Renderer\Options\TimeSeriesRendererOptions;

final readonly class TimeSeriesEmbedOptions implements ChartEmbedOptionsInterface
{
    public const float ASPECT_RATIO_MIN = 0.5;
    public const float ASPECT_RATIO_MAX = 8.0;
    public const float ASPECT_RATIO_STEP = 0.1;

    public function __construct(
        public ?TimeRange $range = null,
        public ?float $aspectRatio = null,
    ) {}

    public static function fromArray(array $data): static
    {
        $range = $data['range'] ?? null;
        $aspectRatio = $data['aspectRatio'] ?? null;

        return new self(
            range: is_string($range) && $range !== '' ? TimeRange::from($range) : null,
            aspectRatio: is_numeric($aspectRatio) ? (float) $aspectRatio : null,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'range' => $this->range?->value,
            'aspectRatio' => $this->aspectRatio,
        ], static fn(mixed $value): bool => $value !== null);
    }

    public function applyTo(RendererOptionsInterface $options): void
    {
        // The submitted style may not match the options built for the rendered one.
        if (!$options instanceof TimeSeriesRendererOptions) {
            return;
        }

        $options->chartConfig->setRange($this->range ?? $options->chartConfig->range);
        $options->chartConfig->setAspectRatio($this->aspectRatio ?? $options->chartConfig->aspectRatio);
    }
}
