<?php

declare(strict_types=1);

namespace App\Metrics\Metric\FrankenPhp;

use App\Metrics\BagAwareTrait;
use App\Metrics\Metric;
use App\Metrics\Metric\BasicMetricInterface;
use App\Metrics\Renderer\Dto\BasicDto;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

#[AsTaggedItem(Metric::FrankenPhpMode->value)]
class FrankenPhpMode implements BasicMetricInterface
{
    use BagAwareTrait;

    public function getMetric(): Metric
    {
        return Metric::FrankenPhpMode;
    }

    public function configureBasicDto(BasicDto $dto, array $options = []): void
    {
        $dto
            ->setValue($this->getFrankenPhpBag()->mode)
        ;
    }
}
