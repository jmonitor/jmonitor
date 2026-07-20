<?php

declare(strict_types=1);

namespace App\Chart\Configurator;

use App\Chart\Dto\ChartConfigurationInterface;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\UX\Chartjs\Model\Chart;

#[AutoconfigureTag('app.chart.configurator')]
interface ChartConfiguratorInterface
{
    public function support(Chart $chart, ChartConfigurationInterface $config): bool;

    public function configure(Chart $chart, ChartConfigurationInterface $config): void;
}
