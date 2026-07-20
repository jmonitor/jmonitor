<?php

declare(strict_types=1);

namespace App\Metrics\Renderer;

use App\Chart\ChartConfigurator;
use App\Metrics\Metric\GaugeMetricInterface;
use App\Metrics\Metric\MetricInterface;
use App\Metrics\Renderer;
use App\Metrics\Renderer\Dto\GaugeDto;
use App\Metrics\Renderer\Options\GaugeRendererOptions;
use App\Metrics\Renderer\Options\RendererOptionsInterface;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;
use Symfony\UX\Chartjs\Model\Chart;
use Twig\Environment;

#[AsTaggedItem(Renderer::Gauge->value)]
readonly class GaugeRenderer implements MetricRendererInterface
{
    public function __construct(
        private Environment $twig,
        private ChartConfigurator $chartConfigurator,
    ) {}

    /**
     * @param GaugeDto $dto
     * @param GaugeRendererOptions $options
     */
    public function render($dto, RendererOptionsInterface $options): string
    {
        // null value already check on "createDto"

        $chart = $this->getChart($dto->value);

        $this->chartConfigurator->configureChart($chart, $options->chartConfig);

        return $this->twig->render($options->template ?? 'dash/project/metrics/gauge/' . $dto->metric->value . '.html.twig', [
            'dto' => $dto,
            'chart' => $chart,
            'options' => $options,
        ]);
    }

    /**
     * @param GaugeMetricInterface $metric
     */
    public function createDto(MetricInterface $metric, array $dtoOptions): ?GaugeDto
    {
        $dto = new GaugeDto($metric->getMetric());
        $metric->configureGauge($dto, $dtoOptions);

        if ($dto->value === null) {
            return null;
        }

        return $dto;
    }

    public function createRendererOptions(): GaugeRendererOptions
    {
        return new GaugeRendererOptions();
    }

    // private function getChart(Metric $metric, mixed $value, array $options): ChartInterface
    private function getChart(float $percent): Chart
    {
        $chart = new Chart(Chart::TYPE_DOUGHNUT);

        $chart->setData([
            'labels' => ['Used', 'Available'],
            'datasets' => [
                [
                    'data' => [$percent, 100 - ($percent)],
                ],
            ],
        ]);

        return $chart;
    }
}
