<?php

declare(strict_types=1);

namespace App\Chart;

use App\Chart\Configurator\ChartConfiguratorInterface;
use App\Chart\Dto\ChartConfigurationInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;
use Symfony\UX\Chartjs\Model\Chart;

/**
 * Transforms a chart's configuration options into a ChartJS configuration.
 */
readonly class ChartConfigurator
{
    public function __construct(
        /**
         * @var iterable<ChartConfiguratorInterface>
         */
        #[AutowireIterator('app.chart.configurator')]
        private iterable $configurators,
    ) {}

    public function configureChart(Chart $chart, ChartConfigurationInterface $config): void
    {
        foreach ($this->configurators as $configurator) {
            if ($configurator->support($chart, $config)) {
                $configurator->configure($chart, $config);
            }
        }
    }
}
