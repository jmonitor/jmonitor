<?php

declare(strict_types=1);

namespace App\Demo\Generator;

use App\Demo\State\DemoState;
use App\Metrics\Consumer\Consumer;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('app.demo_generator')]
interface DemoMetricGeneratorInterface
{
    public function getConsumer(): Consumer;

    /**
     * @return array<string, mixed> the "metrics" payload for the consumer
     */
    public function generate(DemoState $state): array;
}
