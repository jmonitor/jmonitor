<?php

declare(strict_types=1);

namespace App\Metrics\Actions;

use App\AutoRefresh\AutoRefreshContext;
use App\Range\RangeContext;
use App\Metrics\Actions\Dto\Action;
use App\Metrics\Dto\Embed\EmbedOptionsFactory;
use App\Metrics\Dto\Embed\TimeSeriesEmbedOptions;
use App\Metrics\Dto\EmbedDto;
use App\Metrics\Metric;
use App\Metrics\Renderer;
use App\Project\ProjectContext;

readonly class EmbedActionProvider
{
    public function __construct(
        private RangeContext $rangeContext,
        private AutoRefreshContext $autoRefreshContext,
        private ProjectContext $projectContext,
    ) {}

    /** @param array<string, scalar> $metricOptions options the card itself is rendered with */
    public function getDefaultEmbedAction(Metric $metric, ?Renderer $renderer = null, array $metricOptions = []): Action
    {
        $dto = $this->getDefaultEmbed($metric, $renderer, $metricOptions);

        return new Action('embed')
            ->setLabel('Embed')
            ->setRouteName('project.metrics.embed')
            ->setRouteParams([
                'uuid' => $this->projectContext->getCurrentProject()->getUuid(),
                'embed' => $dto->jsonSerialize(),
            ])
            ->setAttribute('data-action', 'app#turboCanvas')
        ;
    }

    /** @param array<string, scalar> $metricOptions */
    private function getDefaultEmbed(Metric $metric, ?Renderer $renderer = null, array $metricOptions = []): EmbedDto
    {
        $renderer ??= $metric->defaultRenderer();
        $chart = EmbedOptionsFactory::createEmpty($renderer);

        if ($chart instanceof TimeSeriesEmbedOptions) {
            $chart = new TimeSeriesEmbedOptions(range: $this->rangeContext->getRangeDto()->range);
        }

        return new EmbedDto(
            metric: $metric,
            renderer: $renderer,
            autoRefresh: $this->autoRefreshContext->isAutoRefresh(),
            chart: $chart,
            metricOptions: $metricOptions,
        );
    }
}
