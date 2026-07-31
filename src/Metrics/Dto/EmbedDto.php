<?php

declare(strict_types=1);

namespace App\Metrics\Dto;

use App\Chart\TimeRange;
use App\Metrics\Dto\Embed\CardEmbedOptions;
use App\Metrics\Dto\Embed\ChartEmbedOptionsInterface;
use App\Metrics\Dto\Embed\EmbedOptionsFactory;
use App\Metrics\Dto\Embed\TimeSeriesEmbedOptions;
use App\Metrics\Metric;
use App\Metrics\Renderer;
use JsonSerializable;
use Symfony\Component\DependencyInjection\Attribute\Exclude;

/**
 * Plain DTO, never a service: excluded from autowiring so its required Metric (enum)
 * constructor argument doesn't break container compilation.
 *
 * Hydration always goes through fromArray(), for both the stored config and the
 * sidebar's query string (see EmbedDtoValueResolver).
 */
#[Exclude]
readonly class EmbedDto implements JsonSerializable
{
    public function __construct(
        public Metric $metric,
        public ?Renderer $renderer = null,
        public bool $autoRefresh = false,
        public CardEmbedOptions $card = new CardEmbedOptions(),
        public ?ChartEmbedOptionsInterface $chart = null,
    ) {}

    /**
     * Rebuilds a DTO from the array shape produced by jsonSerialize().
     *
     * @param array<string, mixed> $data
     *
     * @throws \InvalidArgumentException when any value is unknown or malformed
     */
    public static function fromArray(array $data): self
    {
        try {
            $metric = Metric::from((string) ($data['m'] ?? ''));
            $renderer = isset($data['re']) ? Renderer::from((string) $data['re']) : null;

            $chartData = is_array($data['cc'] ?? null) ? $data['cc'] : [];

            // Legacy shape: the range used to sit at the root, before it moved into the chart options.
            if (!isset($chartData['range']) && isset($data['ra'])) {
                $chartData['range'] = $data['ra'];
            }

            $cardData = is_array($data['c'] ?? null) ? $data['c'] : [];

            // Legacy shape: showProjectName used to sit at the root as "pn".
            if (!isset($cardData['showProjectName']) && isset($data['pn'])) {
                $cardData['showProjectName'] = $data['pn'];
            }

            $chart = EmbedOptionsFactory::hydrate($renderer ?? $metric->defaultRenderer(), $chartData);
            $card = CardEmbedOptions::fromArray($cardData);
        } catch (\ValueError $e) {
            throw new \InvalidArgumentException('Invalid embed configuration.', previous: $e);
        }

        return new self(
            metric: $metric,
            renderer: $renderer,
            autoRefresh: (bool) ($data['ar'] ?? false),
            card: $card,
            chart: $chart,
        );
    }

    public function findRenderer(): Renderer
    {
        return $this->renderer ?? $this->metric->defaultRenderer();
    }

    /** Convenience accessor for the card template, since chart options may be absent. */
    public function getRange(): ?TimeRange
    {
        return $this->chart instanceof TimeSeriesEmbedOptions ? $this->chart->range : null;
    }

    public function jsonSerialize(): mixed
    {
        return array_filter([
            'm' => $this->metric->value,
            're' => $this->renderer?->value,
            'ar' => $this->autoRefresh,
            'c' => $this->card->toArray(),
            'cc' => $this->chart?->toArray(),
        ], static fn(mixed $value): bool => $value !== null && $value !== []);
    }
}
