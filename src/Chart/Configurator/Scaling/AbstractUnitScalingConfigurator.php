<?php

declare(strict_types=1);

namespace App\Chart\Configurator\Scaling;

use App\Chart\Configurator\ChartConfiguratorInterface;
use App\Chart\Dto\ChartConfigurationInterface;
use App\Chart\Dto\TimeSeriesChartConfiguration;
use App\Chart\Units\Bytes;
use App\Chart\Units\Unit;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;
use Symfony\UX\Chartjs\Model\Chart;

abstract readonly class AbstractUnitScalingConfigurator implements ChartConfiguratorInterface
{
    abstract protected function supportsUnit(Unit $unit): bool;

    abstract protected function computeScaleFactor(int $maxValue): float;

    public function support(Chart $chart, ChartConfigurationInterface $config): bool
    {
        return $config instanceof TimeSeriesChartConfiguration
            && $config->unit && $this->supportsUnit($config->unit);
    }

    public function configure(Chart $chart, ChartConfigurationInterface $config): void
    {
        assert($config instanceof TimeSeriesChartConfiguration);

        $maxValue = $this->getMaxValue($chart);
        if ($maxValue <= 0) {
            return;
        }

        $factor = $this->computeScaleFactor($maxValue);
        if ($factor <= 1) {
            return;
        }

        $this->scaleDatasets($chart, $factor);

        if ($config->yMin !== null) {
            $config->setYMin((int) round($config->yMin / $factor));
        }
        if ($config->yMax !== null) {
            $config->setYMax((int) round($config->yMax / $factor));
        }
    }

    protected function getMaxValue(Chart $chart): int
    {
        return (int) max(array_map(
            fn($dataset) => max($dataset['data']),
            $chart->getData()['datasets'],
        ));
    }

    protected function scaleDatasets(Chart $chart, float $factor): void
    {
        $data = $chart->getData();
        $datasets = $data['datasets'] ?? [];

        foreach ($datasets as $di => $dataset) {
            if (!isset($dataset['data']) || !is_array($dataset['data'])) {
                continue;
            }

            foreach ($dataset['data'] as $x => $y) {
                if ($y === null) {
                    continue;
                }

                $datasets[$di]['data'][$x] = round($y / $factor, 2);
            }
        }

        $data['datasets'] = $datasets;
        $chart->setData($data);
    }
}
