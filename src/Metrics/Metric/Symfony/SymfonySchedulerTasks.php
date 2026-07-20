<?php

declare(strict_types=1);

namespace App\Metrics\Metric\Symfony;

use App\Metrics\Metric;
use App\Metrics\Metric\BasicMetricInterface;
use App\Metrics\Renderer\Dto\BasicDto;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

#[AsTaggedItem(Metric::SymfonySchedulerTasks->value)]
class SymfonySchedulerTasks implements BasicMetricInterface
{
    public function getMetric(): Metric
    {
        return Metric::SymfonySchedulerTasks;
    }

    public function configureBasicDto(BasicDto $dto, array $options = []): void
    {
        $dto
            ->setCardTitle('Scheduler - Upcoming tasks')
        ;
    }
}
