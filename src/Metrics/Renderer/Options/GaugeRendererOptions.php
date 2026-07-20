<?php

declare(strict_types=1);

namespace App\Metrics\Renderer\Options;

use App\Chart\Dto\GaugeChartConfiguration;

class GaugeRendererOptions implements RendererOptionsInterface
{
    public private(set) GaugeChartConfiguration $chartConfig;

    /**
     * When the metric has a help entry, the help icon is displayed.
     * This option allows hiding it, e.g. for an embed.
     */
    public private(set) ?bool $displayHelp = null;

    public function __construct()
    {
        $this->chartConfig = new GaugeChartConfiguration();
    }

    public function setDisplayHelp(?bool $displayHelp): static
    {
        $this->displayHelp = $displayHelp;

        return $this;
    }
}
