<?php

declare(strict_types=1);

namespace App\Chart\Configurator;

use App\Chart\Dto\ChartConfigurationInterface;
use App\Chart\Dto\GaugeChartConfiguration;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;
use Symfony\UX\Chartjs\Model\Chart;

/**
 * Default options for gauge charts.
 *
 * https://www.chartjs.org/docs/latest/charts/doughnut.html
 * https://www.chartjs.org/chartjs-plugin-annotation/master/guide/types/doughnutLabel.html
 * https://www.chartjs.org/chartjs-plugin-annotation/master/samples/doughnutLabel/gauge.html
 */
#[AsTaggedItem(priority: 100)]
class GaugeDefaultOptionsConfigurator implements ChartConfiguratorInterface
{
    public function support(Chart $chart, ChartConfigurationInterface $config): bool
    {
        return $chart->getType() === Chart::TYPE_DOUGHNUT;
    }

    /**
     * @param GaugeChartConfiguration $config
     */
    public function configure(Chart $chart, ChartConfigurationInterface $config): void
    {
        $data = $chart->getData();
        $value = $data['datasets'][0]['data'][0] ?? null;

        if ($value === null) {
            return;
        }

        $chart->setOptions([
            'autoFit' => false,
            'aspectRatio' => $config->aspectRatio,

            # https://www.chartjs.org/docs/latest/configuration/elements.html#arc-configuration
            'elements' => [
                'arc' => [
                    'backgroundColor' => [
                        $config->color, // used
                        '#2f3a47', // empty
                    ],
                    'borderColor' => '#191c20',
                    'borderWidth' => 0, // or possibly 0.5
                ],
            ],
            'radius' => '100%', // size relative to the canvas
            'circumference' => 220,
            'rotation' => -110,
            'cutout' => '88%', // makes the doughnut thinner
            //            'borderRadius' => [
            //                'outerStart' => 15,
            //                'outerEnd' => 0,
            //                'innerStart' => 15,
            //                'innerEnd' => 0,
            //            ],
            'animation' => false,
            'plugins' => [
                'annotation' => [
                    'annotations' => [
                        [
                            'type' => 'doughnutLabel',
                            'content' => $value . '%',
                            'font' => [
                                ['size' => 30, 'weight' => '600', 'family' => 'Outfit'],
                            ],
                            'color' => [
                                '#fff',
                            ],
                            'position' => [
                                'y' => '0%',
                            ],
                            // 'yAdjust' => 40,
                        ],
                    ],
                ],
                'legend' => [
                    'display' => false,
                ],
                'subtitle' => [
                    'display' => false,
                    'text' => 'Subtitle',
                ],
                'title' => [
                    'display' => false,
                    'text' => 'Title',
                ],
            ],
        ]);
    }
}
