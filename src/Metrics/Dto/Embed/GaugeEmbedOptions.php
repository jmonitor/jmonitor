<?php

declare(strict_types=1);

namespace App\Metrics\Dto\Embed;

use App\Metrics\Renderer\Options\GaugeRendererOptions;
use App\Metrics\Renderer\Options\RendererOptionsInterface;

final readonly class GaugeEmbedOptions implements ChartEmbedOptionsInterface
{
    public const float ASPECT_RATIO_MIN = 0.8;
    public const float ASPECT_RATIO_MAX = 3.0;
    public const float ASPECT_RATIO_STEP = 0.1;

    public function __construct(
        public ?float $aspectRatio = null,
    ) {}

    public static function fromArray(array $data): static
    {
        $aspectRatio = $data['aspectRatio'] ?? null;

        return new self(aspectRatio: is_numeric($aspectRatio) ? (float) $aspectRatio : null);
    }

    public function toArray(): array
    {
        return array_filter([
            'aspectRatio' => $this->aspectRatio,
        ], static fn(mixed $value): bool => $value !== null);
    }

    public function applyTo(RendererOptionsInterface $options): void
    {
        if (!$options instanceof GaugeRendererOptions) {
            return;
        }

        // Not a user option: the help icon is never shown in an embed.
        $options->setDisplayHelp(false);
        $options->chartConfig->setAspectRatio($this->aspectRatio ?? $options->chartConfig->aspectRatio);
    }
}
