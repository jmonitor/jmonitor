<?php

declare(strict_types=1);

namespace App\Metrics\Metric\Php;

use App\Metrics\BagAwareTrait;
use App\Metrics\Metric;
use App\Metrics\Metric\BasicMetricInterface;
use App\Metrics\Renderer\Dto\BasicDto;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

#[AsTaggedItem(Metric::SymfonyConfigBundles->value)]
class SymfonyConfBundles implements BasicMetricInterface
{
    use BagAwareTrait;

    public function getMetric(): Metric
    {
        return Metric::SymfonyConfigBundles;
    }

    public function configureBasicDto(BasicDto $dto, array $options = []): void
    {
        $dto
            ->setCardTemplate('dash/project/metrics/card/custom/card-tabs.html.twig')
        ;
    }
}
