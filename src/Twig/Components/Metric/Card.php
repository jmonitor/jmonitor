<?php

namespace App\Twig\Components\Metric;

use App\Metrics\Metric;
use App\Metrics\MetricDtoProvider;
use App\Metrics\Renderer;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;
use Symfony\UX\TwigComponent\Attribute\ExposeInTemplate;
use Symfony\UX\TwigComponent\Attribute\PostMount;

#[AsTwigComponent]
class Card
{
    public ?Metric $metric = null;
    public ?Renderer $renderer = null;
    public array $options = [];

    private readonly MetricDtoProvider $metricDtoProvider;

    public function __construct(MetricDtoProvider $metricDtoProvider)
    {
        $this->metricDtoProvider = $metricDtoProvider;
    }

    #[ExposeInTemplate]
    public function getDto()
    {
        return $this->metricDtoProvider->getDto($this->metric, $this->renderer, $this->options);
    }

    public function setMetric(string $metric): void
    {
        $this->metric = Metric::from($metric);
    }

    public function setRenderer(?string $renderer): void
    {
        $this->renderer = $renderer ? Renderer::from($renderer) : null;
    }

    #[PostMount]
    public function postMount(): void
    {
        $this->renderer ??= $this->metric?->defaultRenderer();
    }
}
