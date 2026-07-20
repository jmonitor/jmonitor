<?php

declare(strict_types=1);

namespace App\Twig\Components\Metric;

use App\Metrics\Actions\DefaultActionsProvider;
use App\Metrics\Actions\Dto\Action;
use App\Metrics\Renderer;
use App\Metrics\Renderer\Dto\AbstractDto;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;
use Symfony\UX\TwigComponent\Attribute\ExposeInTemplate;

#[AsTwigComponent]
class Actions
{
    // TODO an ActionsAwareDto interface ?
    public AbstractDto $dto;
    public Renderer $renderer;

    private readonly DefaultActionsProvider $defaultActionsProvider;

    public function __construct(DefaultActionsProvider $defaultActionsProvider)
    {
        $this->defaultActionsProvider = $defaultActionsProvider;
    }

    /**
     * @return Action[]
     */
    #[ExposeInTemplate]
    public function getActions(): array
    {
        return $this->defaultActionsProvider->getDefaultActions($this->dto->metric, $this->renderer);
    }
}
