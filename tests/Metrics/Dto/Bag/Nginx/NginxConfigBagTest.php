<?php

declare(strict_types=1);

namespace App\Tests\Metrics\Dto\Bag\Nginx;

use App\Entity\Project;
use App\Metrics\Consumer\Consumer;
use App\Metrics\Dto\Bag\Nginx\NginxBag;
use App\Metrics\Dto\MetricBagDto;
use PHPUnit\Framework\TestCase;

class NginxConfigBagTest extends TestCase
{
    private function makeBag(array $config, ?int $cpuCount = 4, array $status = []): NginxBag
    {
        $bag = MetricBagDto::create(
            $this->createMock(Project::class),
            Consumer::NGINX,
            1,
            ['config' => $config, 'cpu_count' => $cpuCount, 'status' => $status],
            new \DateTimeImmutable(),
            false,
        );

        $this->assertInstanceOf(NginxBag::class, $bag);
        /** @var NginxBag $bag */
        return $bag;
    }

    public function testMaxConnectionsUsesCpuCountWhenWorkerProcessesIsAuto(): void
    {
        $bag = $this->makeBag(['worker_processes' => 'auto', 'worker_connections' => 1024]);

        $this->assertSame('auto', $bag->config->workerProcesses);
        $this->assertSame(4096, $bag->config->maxConnections);
    }

    public function testMaxConnectionsIsNullWhenWorkerProcessesIsAutoAndCpuCountIsUnknown(): void
    {
        $bag = $this->makeBag(['worker_processes' => 'auto', 'worker_connections' => 1024], cpuCount: null);

        $this->assertNull($bag->config->maxConnections);
    }

    public function testMaxConnectionsWithExplicitWorkerProcesses(): void
    {
        $bag = $this->makeBag(['worker_processes' => '2', 'worker_connections' => 768]);

        $this->assertSame(2, $bag->config->workerProcesses);
        $this->assertSame(1536, $bag->config->maxConnections);
    }

    public function testDirectivesMissingFromTheConfigAreNull(): void
    {
        $bag = $this->makeBag(['worker_processes' => null, 'worker_connections' => null, 'ssl_protocols' => null], status: ['active' => 3]);

        $this->assertNull($bag->config->workerProcesses);
        $this->assertNull($bag->config->sslProtocols);
        $this->assertNull($bag->config->maxConnections);
        $this->assertNull($bag->status->activeConnectionsPercent);
    }
}
