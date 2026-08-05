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
    private const int MAX_METRIC_OPTIONS = 8;
    private const int MAX_METRIC_OPTION_LENGTH = 64;

    /**
     * @param array<string, scalar> $metricOptions options of the metric service itself (which
     *                                             Caddy handler, which Redis database...), as
     *                                             the metric card was rendered with
     */
    public function __construct(
        public Metric $metric,
        public ?Renderer $renderer = null,
        public bool $autoRefresh = false,
        public CardEmbedOptions $card = new CardEmbedOptions(),
        public ?ChartEmbedOptionsInterface $chart = null,
        public array $metricOptions = [],
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
            $metric = Metric::from(self::expectScalar($data['m'] ?? ''));
            $renderer = isset($data['re']) ? Renderer::from(self::expectScalar($data['re'])) : null;

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
            $metricOptions = self::hydrateMetricOptions(is_array($data['o'] ?? null) ? $data['o'] : []);
        } catch (\ValueError $e) {
            throw new \InvalidArgumentException('Invalid embed configuration.', previous: $e);
        }

        return new self(
            metric: $metric,
            renderer: $renderer,
            autoRefresh: (bool) ($data['ar'] ?? false),
            card: $card,
            chart: $chart,
            metricOptions: $metricOptions,
        );
    }

    /**
     * Metric options say *which* series a card shows; their semantics belong to the metric's
     * own OptionsResolver, which rejects anything it doesn't declare. Hydration only bounds
     * what a crafted query string can carry, and restores integer-like values: a query string
     * hands everything back as a string, where those resolvers type them as int.
     *
     * @param array<array-key, mixed> $data
     *
     * @return array<string, scalar>
     */
    private static function hydrateMetricOptions(array $data): array
    {
        if (count($data) > self::MAX_METRIC_OPTIONS) {
            throw new \ValueError('Too many metric options.');
        }

        $options = [];

        foreach ($data as $name => $value) {
            if (!is_string($name) || !preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $name)) {
                throw new \ValueError('Unusable metric option name.');
            }

            if (!is_scalar($value)) {
                throw new \ValueError('Expected a scalar value.');
            }

            if (is_string($value) && strlen($value) > self::MAX_METRIC_OPTION_LENGTH) {
                throw new \ValueError('Metric option value is too long.');
            }

            $asInt = is_string($value) ? filter_var($value, FILTER_VALIDATE_INT) : false;
            $options[$name] = $asInt === false ? $value : $asInt;
        }

        return $options;
    }

    /**
     * A malformed query string can carry an array where a scalar is expected (e.g.
     * "embed[m][]=x"); casting that to string emits a PHP warning before Metric::from()/
     * Renderer::from() even get a chance to reject it. Fail the same way as any other
     * malformed value instead.
     */
    private static function expectScalar(mixed $value): string
    {
        if (!is_scalar($value)) {
            throw new \ValueError('Expected a scalar value.');
        }

        return (string) $value;
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
            'o' => $this->metricOptions,
        ], static fn(mixed $value): bool => $value !== null && $value !== []);
    }
}
