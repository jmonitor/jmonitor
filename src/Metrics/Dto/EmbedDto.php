<?php

declare(strict_types=1);

namespace App\Metrics\Dto;

use App\Chart\TimeRange;
use App\Metrics\Metric;
use App\Metrics\Renderer;
use JsonSerializable;
use Symfony\Component\Serializer\Attribute\SerializedName;
use Symfony\Component\Validator\Constraints as Assert;

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
