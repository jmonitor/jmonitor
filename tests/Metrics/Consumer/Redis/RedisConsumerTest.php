<?php

declare(strict_types=1);

namespace App\Tests\Metrics\Consumer\Redis;

use App\Entity\Project;
use App\Metrics\Consumer\Consumer;
use App\Metrics\Consumer\Redis\RedisConsumer;
use App\Metrics\DeltaCalculator;
use App\Metrics\Dto\Bag\Redis\RedisBag;
use App\Metrics\Dto\MetricBagDto;
use App\Plan\PlanResolver;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

class RedisConsumerTest extends TestCase
{
    public function testNormalizedBagCarriesOpsPerSecond(): void
    {
        $consumer = new RedisConsumer($deltaCalculator = new DeltaCalculator(new ArrayAdapter()), $this->createMock(PlanResolver::class));
        $project = $this->createMock(Project::class);
        $project->method('getId')->willReturn(1);
        $start = new \DateTimeImmutable('2026-10-21 10:00:00');

        $first = $consumer->normalizeBag($this->bag(5000, $project, $start));
        $this->assertArrayNotHasKey('ops_per_sec', $first->all('stats'));
        $deltaCalculator->reset();

        $bag = $consumer->normalizeBag($this->bag(5300, $project, $start->modify('+15 seconds')));

        $this->assertInstanceOf(RedisBag::class, $bag);
        $this->assertSame(20.0, $bag->stats->opsPerSec);
    }

    private function bag(int $commands, Project $project, \DateTimeImmutable $receivedAt): MetricBagDto
    {
        return MetricBagDto::create($project, Consumer::REDIS, 1, ['stats' => ['total_commands_processed' => $commands]], $receivedAt, false);
    }
}
