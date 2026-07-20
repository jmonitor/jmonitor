<?php

declare(strict_types=1);

namespace App\Metrics\Actions;

use App\AutoRefresh\AutoRefreshContext;
use App\Range\RangeContext;
use App\Metrics\Actions\Dto\Action;
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

    public function getDefaultEmbedAction(Metric $metric, ?Renderer $renderer = null): Action
    {
        $dto = $this->getDefaultEmbed($metric, $renderer);

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

    private function getDefaultEmbed(Metric $metric, ?Renderer $renderer = null): EmbedDto
    {
        $renderer ??= $metric->defaultRenderer();

        return new EmbedDto(
            metric: $metric,
            renderer: $renderer,
            range: $renderer->supportRange() ? $this->rangeContext->getRangeDto()->range : null,
            autoRefresh: $this->autoRefreshContext->isAutoRefresh(),
            //            card: true,
            chartConfig: [],
        );
    }
}
