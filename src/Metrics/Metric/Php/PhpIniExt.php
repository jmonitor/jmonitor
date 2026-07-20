<?php

declare(strict_types=1);

namespace App\Metrics\Metric\Php;

use App\Metrics\BagAwareTrait;
use App\Metrics\Consumer\Consumer;
use App\Metrics\Metric;
use App\Metrics\Metric\BasicMetricInterface;
use App\Metrics\Renderer\Dto\BasicDto;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

#[AsTaggedItem(Metric::PhpIniExt->value)]
class PhpIniExt implements BasicMetricInterface
{
    use BagAwareTrait;

    public function getMetric(): Metric
    {
        return Metric::PhpIniExt;
    }

    public function configureBasicDto(BasicDto $dto, array $options = []): void
    {
        $bag = $this->getPhpBag();

        $dto
            ->setCardTemplate('dash/project/metrics/card/custom/card-tabs.html.twig')
            ->setContext([
                'nbIni' => count($bag->iniFiles) + ($bag->iniFile ? 1 : 0),
                'nbExt' => count($bag->loadedExtensions),
            ])
        ;
    }
}
