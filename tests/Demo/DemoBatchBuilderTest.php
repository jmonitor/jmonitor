<?php

declare(strict_types=1);

namespace App\Tests\Demo;

use App\Demo\DemoBatchBuilder;
use App\Demo\Generator\DemoMetricGeneratorInterface;
use App\Demo\State\DemoState;
use App\Entity\Enums\Component;
use App\Entity\Project;
use App\Metrics\Consumer\Consumer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

class DemoBatchBuilderTest extends TestCase
{
    public function testBuildsOnlyEnabledComponents(): void
    {
        $systemGen = $this->makeGenerator(Consumer::SYSTEM, ['k' => 1]);
        $redisGen = $this->makeGenerator(Consumer::REDIS, ['k' => 2]);

        $project = $this->createMock(Project::class);
        $project->method('hasComponent')->willReturnCallback(
            fn(Component $c): bool => $c === Component::System,
        );

        $builder = new DemoBatchBuilder([$systemGen, $redisGen]);
        $batch = $builder->build($project, new DemoState(new ArrayAdapter()));

        $this->assertCount(1, $batch);
        $this->assertSame('system', $batch[0]['name']);
        $this->assertSame(1, $batch[0]['version']);
        $this->assertFalse($batch[0]['threw']);
        $this->assertSame(['k' => 1], $batch[0]['metrics']);
        $this->assertGreaterThan(0.0, $batch[0]['duration']);
    }

    private function makeGenerator(Consumer $consumer, array $metrics): DemoMetricGeneratorInterface
    {
        $gen = $this->createMock(DemoMetricGeneratorInterface::class);
        $gen->method('getConsumer')->willReturn($consumer);
        $gen->method('generate')->willReturn($metrics);

        return $gen;
    }
}
