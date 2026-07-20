<?php

declare(strict_types=1);

namespace App\Chart\Configurator;

use App\Chart\Dto\ChartConfigurationInterface;
use App\Chart\Dto\TimeSeriesChartConfiguration;
use App\Range\RangeContext;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;
use Symfony\UX\Chartjs\Model\Chart;

/**
 * Default options for line charts.
 */
#[AsTaggedItem(priority: -100)]
readonly class LineDefaultOptionsConfigurator implements ChartConfiguratorInterface
{
    public function __construct(
        private RangeContext $rangeContext,
    ) {}

    public function support(Chart $chart, ChartConfigurationInterface $config): bool
    {
        return $config instanceof TimeSeriesChartConfiguration;
    }

    /**
     * @param TimeSeriesChartConfiguration $config
     */
    public function configure(Chart $chart, ChartConfigurationInterface $config): void
    {
        $range = $config->range ?? $this->rangeContext->getRangeDto()->range;

        $options = [
            'animation' => false,
            'aspectRatio' => $config->aspectRatio,

            'elements' => [
                // Point elements are used to represent the points in a line, radar or bubble chart.
                // https://www.chartjs.org/docs/latest/configuration/elements.html#point-styles
                'point' => [
                    'radius' => 0, // point size
                    'pointStyle' => 'circle',
                    'hitRadius' => 4,
                    'hoverRadius' => 3,
                    'borderWidth' => 0, // irrelevant while radius is 0
                ],

                // line
                // https://www.chartjs.org/docs/latest/configuration/elements.html#line-configuration
                'line' => [
                    'tension' => 0.2, // line curvature
                    'backgroundColor' => '#528ef9',
                    'borderWidth' => 1.5, // line stroke width
                    'borderColor' => '#528ef9',
                    'spanGaps' => 1, // If true, lines will be drawn between points with no or null data. If false, points with null data will create a break in the line. Can also be a number specifying the maximum gap length to span. The unit of the value depends on the scale used.
                ],

                // bar
                // https://www.chartjs.org/docs/latest/configuration/elements.html#bar-configuration
                'bar' => [
                    'backgroundColor' => '#FE5F7E',
                    'minBarLength' => 50, // Set this to ensure that bars have a minimum length in pixels
                ],

                // Arcs are used in the polar area, doughnut and pie charts
                // https://www.chartjs.org/docs/latest/configuration/elements.html#arc-configuration
                'arc' => [

                ],
            ],

            // interaction
            'interaction' => [
                'intersect' => false,
                'mode' => 'nearest',
                'axis' => 'x',
            ],
            'plugins' => [
                // https://www.chartjs.org/docs/latest/configuration/legend.html
                'legend' => [
                    'display' => count($chart->getData()['datasets'] ?? []) > 1, // TODO review the other legend options (colors etc.) when it is displayed
                ],

                // tooltip
                // https://www.chartjs.org/docs/latest/configuration/tooltip.html
                // 'tooltip' => [],

                // subtitle
                // https://www.chartjs.org/docs/latest/configuration/subtitle.html
                // 'subtitle' => [],
            ],

            // https://www.chartjs.org/docs/latest/axes/
            // https://www.chartjs.org/docs/latest/general/performance.html: be specific when possible
            'scales' => [
                'x' => [
                    'display' => false,
                    'type' => 'time', // https://www.chartjs.org/docs/latest/axes/cartesian/time.html
                    // min/max not strictly required, since empty slots do exist as data points (null values).
                    // Emit an explicit timezone offset (RFC3339 / ISO8601) so Chart.js + the moment
                    // adapter anchor the bounds to an absolute instant: the data points are UTC, and a
                    // timezone-naive string would be parsed in the browser's zone, shifting a non-UTC
                    // viewer's series out of the visible window (empty charts).
                    'min' => $range->asStartDateTime()->format('c'),
                    'max' => $range->asEndDateTime()->format('c'),

                    // https://www.chartjs.org/docs/latest/axes/styling.html#grid-line-configuration
                    'grid' => [
                        'display' => false,
                        'drawTicks' => true, // adds a bit of spacing, even with grid display false
                        'drawOnChartArea' => false, // hides the grid lines while keeping the ticks visible
                    ],
                    'ticks' => [ // axis tick labels
                        'display' => true,
                        'autoSkipPadding' => 20,
                    ],
                    'border' => [
                        'display' => true,
                    ],
                ],
                'y' => [
                    'display' => true,
                    'type' => 'linear',
                    'min' => $config->yMin,
                    'max' => $config->yMax,
                    'suggestedMin' => $config->suggestedYMin,

                    // https://www.chartjs.org/docs/latest/axes/styling.html#grid-line-configuration
                    'grid' => [
                        'display' => true,
                        'drawTicks' => true,
                        'drawOnChartArea' => true,
                    ],
                    'ticks' => [
                        'display' => true,
                        'precision' => 0,
                    ],
                    'border' => [
                        'display' => true,
                    ],
                    'grace' => '5%', // adds some headroom above and below // TODO should be configurable per metric (unwanted on cpu load for instance)
                ],
            ],
        ];

        $chart->setOptions($options);
    }
}
