<?php

declare(strict_types=1);

namespace App\Metrics\Metric\FrankenPhp;

use App\Metrics\BagAwareTrait;
use App\Metrics\Metric;
use App\Metrics\Metric\BasicMetricInterface;
use App\Metrics\Renderer\Dto\BasicDto;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

#[AsTaggedItem(Metric::FrankenPhpVersion->value)]
class FrankenPhpVersion implements BasicMetricInterface
{
    use BagAwareTrait;

    public function getMetric(): Metric
    {
        return Metric::FrankenPhpVersion;
    }

    public function configureBasicDto(BasicDto $dto, array $options = []): void
    {
        $dto
            ->setValueAvailable($this->getFrankenPhpBag()->frankenPhpVersion !== null)
            ->setValue($this->getFrankenPhpBag()->frankenPhpVersion)
        ;
    }
}
