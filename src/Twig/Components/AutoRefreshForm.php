<?php

declare(strict_types=1);

namespace App\Twig\Components;

use App\AutoRefresh\AutoRefreshContext;
use App\AutoRefresh\Dto\AutoRefreshDto;
use App\Entity\Enums\Component;
use App\Metrics\Dto\MetricBagDto;
use App\Metrics\MetricsBagProvider;
use Symfony\Component\Form\FormView;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;
use Symfony\UX\TwigComponent\Attribute\ExposeInTemplate;

#[AsTwigComponent]
class AutoRefreshForm
{
    public Component $component;
    private readonly AutoRefreshContext $autoRefreshContext;
    private MetricsBagProvider $bagProvider;

    public function __construct(AutoRefreshContext $autoRefreshContext, MetricsBagProvider $bagProvider)
    {
        $this->autoRefreshContext = $autoRefreshContext;
        $this->bagProvider = $bagProvider;
    }

    #[ExposeInTemplate]
    public function getForm(): FormView
    {
        return $this->autoRefreshContext->getForm()->createView();
    }

    #[ExposeInTemplate]
    public function getRefreshDto(): AutoRefreshDto
    {
        return $this->autoRefreshContext->getDto();
    }

    #[ExposeInTemplate]
    public function getBag(): ?MetricBagDto
    {
        $bags = $this->bagProvider->getComponentBags($this->component);

        if (count($bags) === 1) {
            return array_first($bags);
        }

        // take the most recent bag
        foreach ($bags as $bag) {
            return $bag;
        }

        return null;
    }
}
