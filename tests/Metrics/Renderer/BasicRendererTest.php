<?php

declare(strict_types=1);

namespace App\Tests\Metrics\Renderer;

use App\Metrics\Dto\MetricBagDto;
use App\Metrics\Metric\Symfony\SymfonySchedulerNextTask;
use App\Metrics\MetricsBagProvider;
use App\Metrics\Renderer\BasicRenderer;
use PHPUnit\Framework\TestCase;
use Twig\Environment;

class BasicRendererTest extends TestCase
{
    public function testValueIsUnavailableWhenTheComponentHasNoBag(): void
    {
        // Collector not running: no bag cached for the metric's component.
        $bagProvider = $this->createMock(MetricsBagProvider::class);
        $bagProvider->method('getComponentBags')->willReturn([]);

        $renderer = new BasicRenderer($this->createMock(Environment::class), $bagProvider);

        $dto = $renderer->createDto(new SymfonySchedulerNextTask(), []);

        $this->assertNotNull($dto);
        $this->assertFalse($dto->valueAvailable);
    }

    public function testValueIsAvailableWhenTheComponentHasABag(): void
    {
        $bagProvider = $this->createMock(MetricsBagProvider::class);
        $bagProvider->method('getComponentBags')->willReturn(['symfony' => $this->createMock(MetricBagDto::class)]);

        $renderer = new BasicRenderer($this->createMock(Environment::class), $bagProvider);

        $dto = $renderer->createDto(new SymfonySchedulerNextTask(), []);

        $this->assertNotNull($dto);
        $this->assertTrue($dto->valueAvailable);
    }
}
