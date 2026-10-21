<?php

declare(strict_types=1);

namespace App\Tests\Metrics\Renderer;

use App\Chart\ChartConfigurator;
use App\Entity\Project;
use App\Metrics\Consumer\Consumer;
use App\Metrics\Dto\Bag\Nginx\NginxBag;
use App\Metrics\Dto\MetricBagDto;
use App\Metrics\Metric\Nginx\NginxActiveConnections;
use App\Metrics\MetricsBagProvider;
use App\Metrics\Renderer\GaugeRenderer;
use PHPUnit\Framework\TestCase;
use Twig\Environment;

class GaugeRendererTest extends TestCase
{
    public function testGaugeWithoutValueHasNoDto(): void
    {
        $dto = $this->renderer()->createDto($this->metric(['config' => ['worker_processes' => 'auto'], 'status' => ['active' => 3]]), []);

        $this->assertNull($dto);
    }

    public function testGaugeDeclaredUnavailableKeepsItsReason(): void
    {
        $dto = $this->renderer()->createDto($this->metric(['config' => ['config_path' => null], 'status' => ['active' => 3]]), []);

        $this->assertNotNull($dto);
        $this->assertFalse($dto->valueAvailable);
        $this->assertNotNull($dto->emptyDataReason);
    }

    private function renderer(): GaugeRenderer
    {
        return new GaugeRenderer($this->createMock(Environment::class), $this->createMock(ChartConfigurator::class));
    }

    private function metric(array $metrics): NginxActiveConnections
    {
        $bag = MetricBagDto::create($this->createMock(Project::class), Consumer::NGINX, 1, $metrics, new \DateTimeImmutable(), false);
        $this->assertInstanceOf(NginxBag::class, $bag);

        $bagProvider = $this->createMock(MetricsBagProvider::class);
        $bagProvider->method('getLastBag')->willReturn($bag);

        $metric = new NginxActiveConnections();
        $metric->setBagProvider($bagProvider);

        return $metric;
    }
}
