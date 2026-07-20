<?php

declare(strict_types=1);

namespace App\Demo;

use App\Demo\Generator\DemoMetricGeneratorInterface;
use App\Demo\State\DemoState;
use App\Entity\Project;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

readonly class DemoBatchBuilder
{
    /**
     * @param iterable<DemoMetricGeneratorInterface> $generators
     */
    public function __construct(
        #[AutowireIterator('app.demo_generator')]
        private iterable $generators,
    ) {}

    /**
     * @return array<int, array{name: string, version: int, metrics: array, threw: bool, duration: float}>
     */
    public function build(Project $project, DemoState $state): array
    {
        $batch = [];

        foreach ($this->generators as $generator) {
            $consumer = $generator->getConsumer();

            if (!$project->hasComponent($consumer->getComponent())) {
                continue;
            }

            $batch[] = [
                'name' => $consumer->value,
                'version' => 1,
                'metrics' => $generator->generate($state),
                'threw' => false,
                'duration' => round(mt_rand(1, 120) / 1000, 3),
            ];
        }

        return $batch;
    }
}
