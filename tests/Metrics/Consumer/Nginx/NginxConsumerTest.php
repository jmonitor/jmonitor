<?php

declare(strict_types=1);

namespace App\Tests\Metrics\Consumer\Nginx;

use App\Entity\Project;
use App\Metrics\Consumer\Consumer;
use App\Metrics\Consumer\Nginx\NginxConsumer;
use App\Metrics\DeltaCalculator;
use App\Metrics\Dto\Bag\Nginx\NginxBag;
use App\Metrics\Dto\MetricBagDto;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

class NginxConsumerTest extends TestCase
{
    public function testNormalizedBagCarriesRequestsPerSecond(): void
    {
        $consumer = new NginxConsumer($deltaCalculator = new DeltaCalculator(new ArrayAdapter()));
        $project = $this->createMock(Project::class);
        $project->method('getId')->willReturn(1);
        $start = new \DateTimeImmutable('2026-10-21 10:00:00');

        $consumer->normalizeBag($this->bag(1000, $project, $start));
        $deltaCalculator->reset();

        $bag = $consumer->normalizeBag($this->bag(1030, $project, $start->modify('+15 seconds')));

        $this->assertInstanceOf(NginxBag::class, $bag);
        $this->assertSame(2.0, $bag->status->requestsPerSecond);
    }

    private function bag(int $requests, Project $project, \DateTimeImmutable $receivedAt): MetricBagDto
    {
        return MetricBagDto::create($project, Consumer::NGINX, 1, ['status' => ['requests' => $requests]], $receivedAt, false);
    }
}
