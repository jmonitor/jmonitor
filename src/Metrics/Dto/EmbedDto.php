<?php

declare(strict_types=1);

namespace App\Metrics\Dto;

use App\Chart\TimeRange;
use App\Metrics\Metric;
use App\Metrics\Renderer;
use JsonSerializable;
use Symfony\Component\DependencyInjection\Attribute\Exclude;
use Symfony\Component\Serializer\Attribute\SerializedName;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Plain DTO, never a service: excluded from autowiring so its required Metric (enum)
 * constructor argument doesn't break container compilation when a controller action
 * takes an optional #[MapQueryString] EmbedDto (see MetricsController::embed()).
 */
#[Exclude]
readonly class EmbedDto implements JsonSerializable
{
    public function __construct(
        #[SerializedName('m')]
        #[Assert\NotBlank]
        public Metric $metric,
        #[SerializedName('re')]
        public ?Renderer $renderer,
        #[SerializedName('ra')]
        public ?TimeRange $range,
        #[SerializedName('ar')]
        public bool $autoRefresh = false,
        #[SerializedName('cc')]
        public ?array $chartConfig = null,
    ) {}

    /**
     * Rebuilds a DTO from the array shape produced by jsonSerialize() (DB-stored embed config).
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $chartConfig = $data['cc'] ?? null;

        if (is_array($chartConfig) && isset($chartConfig['aspectRatio'])) {
            $chartConfig['aspectRatio'] = is_numeric($chartConfig['aspectRatio']) ? (float) $chartConfig['aspectRatio'] : null;
        }

        return new self(
            metric: Metric::from($data['m']),
            renderer: isset($data['re']) ? Renderer::from($data['re']) : null,
            range: isset($data['ra']) ? TimeRange::from($data['ra']) : null,
            autoRefresh: (bool) ($data['ar'] ?? false),
            chartConfig: $chartConfig,
        );
    }

    public function with(?Renderer $renderer = null, ?TimeRange $range = null, ?bool $autoRefresh = null, ?array $chartConfig = null): self
    {
        return new self(
            $this->metric,
            $renderer ?? $this->renderer,
            $range ?? $this->range,
            $autoRefresh ?? $this->autoRefresh,
            $chartConfig ?? $this->chartConfig,
        );
    }

    public function findRenderer(): Renderer
    {
        return $this->renderer ?? $this->metric->defaultRenderer();
    }

    public function jsonSerialize(): mixed
    {
        return array_filter([
            'm' => $this->metric->value,
            're' => $this->renderer?->value,
            'ra' => $this->range?->value,
            'ar' => $this->autoRefresh,
            'cc' => $this->chartConfig,
        ], fn($v): bool => $v !== null);
    }
}
