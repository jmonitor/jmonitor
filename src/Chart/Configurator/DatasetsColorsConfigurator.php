<?php

declare(strict_types=1);

namespace App\Chart\Configurator;

use App\Chart\ChartColor;
use App\Chart\Dto\ChartConfigurationInterface;
use App\Chart\Dto\TimeSeriesChartConfiguration;
use Symfony\UX\Chartjs\Model\Chart;

/**
 * Applies the colors from the configuration (or the default ones) to the ChartJS datasets.
 */
readonly class DatasetsColorsConfigurator implements ChartConfiguratorInterface
{
    public function support(Chart $chart, ChartConfigurationInterface $config): bool
    {
        return $config instanceof TimeSeriesChartConfiguration;
    }

    /**
     * @param TimeSeriesChartConfiguration $config
     */
    public function configure(Chart $chart, ChartConfigurationInterface $config): void
    {
        $data = $chart->getData();
        $datasets = $data['datasets'] ?? [];

        if ($datasets === []) {
            return;
        }

        foreach ($datasets as $i => $dataset) {
            $fieldName = $dataset['field_name'];

            $color = $config->colors[$fieldName] ?? ChartColor::atIndex($i)->value;

            $datasets[$i]['borderColor'] = $dataset['borderColor'] ?? $color;
            $datasets[$i]['backgroundColor'] = $dataset['backgroundColor'] ?? $color;
        }

        $data['datasets'] = $datasets;
        $chart->setData($data);
    }
}
