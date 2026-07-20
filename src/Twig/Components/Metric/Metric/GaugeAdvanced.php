<?php

declare(strict_types=1);

namespace App\Twig\Components\Metric\Metric;

use App\Metrics\Dto\MetricBagDto;
use App\Metrics\MetricsBagProvider;
use App\Metrics\Renderer\Dto\GaugeDto;
use Symfony\UX\Chartjs\Model\Chart;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;
use Symfony\UX\TwigComponent\Attribute\ExposeInTemplate;

#[AsTwigComponent]
class GaugeAdvanced
{
    public GaugeDto $dto;
    public Chart $chart;
    private MetricsBagProvider $metricsBagProvider;

    public function __construct(MetricsBagProvider $metricsBagProvider)
    {
        $this->metricsBagProvider = $metricsBagProvider;
    }

    #[ExposeInTemplate]
    public function getBags(): array
    {
        return $this->metricsBagProvider->getComponentBags($this->dto->metric->component());
    }

    #[ExposeInTemplate]
    public function getBag(): MetricBagDto
    {
        return array_first($this->getBags());
    }
}
