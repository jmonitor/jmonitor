<?php

declare(strict_types=1);

namespace App\Metrics\Renderer;

use App\Chart\ChartConfigurator;
use App\Metrics\Metric\MetricInterface;
use App\Metrics\Metric\TimeSeriesMetricInterface;
use App\Metrics\Renderer\Dto\TimeSerieDto;
use App\Metrics\Renderer\Error\EmptyDataException;
use App\Metrics\Renderer\Options\TimeSeriesRendererOptions;
use App\Metrics\Renderer\Options\RendererOptionsInterface;
use App\Security\Voter\Right\Right;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\UX\Chartjs\Model\Chart;
use Twig\Environment;

/**
 * Generic renderer for all InfluxDB-based charts (line, bar, etc.).
 * Registered for each supported chart type.
 */
abstract readonly class InfluxChartRenderer implements MetricRendererInterface
{
    public function __construct(
        private Environment $twig,
        private InfluxChartDataProvider $influxDatasetsProvider,
        private ChartConfigurator $chartConfigurator,
        private Security $security,
    ) {}

    /**
     * @param TimeSerieDto $dto
     * @param TimeSeriesRendererOptions $options
     */
    public function render($dto, RendererOptionsInterface $options): string
    {
        if (!$this->security->isGranted(Right::TIME_SERIES_CHART->value)) {
            return $this->twig->render('dash/project/metrics/line/_rendering_not_granted.html.twig');
        }

        $datasets = $this->influxDatasetsProvider->getDatasets($dto, $options->chartConfig->range);

        if (!$datasets) {
            throw new EmptyDataException();
        }

        $chart = new Chart($options->chartConfig->chartType);
        $chart->setData(['datasets' => $datasets]);

        $this->chartConfigurator->configureChart($chart, $options->chartConfig);

        return $this->twig->render('dash/project/metrics/line/line.html.twig', [
            'dto' => $dto,
            'chart' => $chart,
        ]);
    }

    /**
     * @param TimeSeriesMetricInterface $metric
     */
    public function createDto(MetricInterface $metric, array $dtoOptions): TimeSerieDto
    {
        $dto = new TimeSerieDto($metric->getMetric());
        $metric->configureTimeSerie($dto, $dtoOptions);

        return $dto;
    }

    public function createRendererOptions(): TimeSeriesRendererOptions
    {
        return new TimeSeriesRendererOptions();
    }
}
