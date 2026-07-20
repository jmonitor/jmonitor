<?php

declare(strict_types=1);

namespace App\Metrics\Metric\Apache;

use App\Metrics\BagAwareTrait;
use App\Metrics\Metric;
use App\Metrics\Metric\BasicMetricInterface;
use App\Metrics\Renderer\Dto\BasicDto;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

#[AsTaggedItem(Metric::ApacheLoadAverage->value)]
class ApacheLoadAverage implements BasicMetricInterface
{
    use BagAwareTrait;

    public function getMetric(): Metric
    {
        return Metric::ApacheLoadAverage;
    }

    public function configureBasicDto(BasicDto $dto, array $options = []): void
    {
        $bag = $this->getApacheBag();

        $dto
            ->setValueAvailable($bag->load1 !== null && $bag->load5 !== null && $bag->load15 !== null)
            ->setValue([
                'load1' => $bag->load1,
                'load5' => $bag->load5,
                'load15' => $bag->load15,
            ]);
        ;
    }
}
