<?php

declare(strict_types=1);

namespace App\Metrics\Actions;

use App\Metrics\Actions\Dto\Action;
use App\Metrics\Metric;
use App\Metrics\Renderer;

readonly class DefaultActionsProvider
{
    public function __construct(
        private EmbedActionProvider $embedActionProvider,
    ) {}

    /**
     * @return Action[]
     */
    public function getDefaultActions(Metric $metric, ?Renderer $renderer): array
    {
        return [
            $this->embedActionProvider->getDefaultEmbedAction($metric, $renderer),
        ];
    }
}
