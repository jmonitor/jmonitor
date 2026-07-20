<?php

declare(strict_types=1);

namespace App\Metrics\Metric\Symfony;

use App\Metrics\Metric;
use App\Metrics\Metric\BasicMetricInterface;
use App\Metrics\Renderer\Dto\BasicDto;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

#[AsTaggedItem(Metric::SymfonySchedulerNextTask->value)]
class SymfonySchedulerNextTask implements BasicMetricInterface
{
    public function getMetric(): Metric
    {
        return Metric::SymfonySchedulerNextTask;
    }

    public function configureBasicDto(BasicDto $dto, array $options = []): void
    {
        $dto
            ->setCardTitle('Scheduler - Next task')
        ;
    }
}
