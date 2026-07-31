<?php

declare(strict_types=1);

namespace App\Tests\Metrics;

use App\Metrics\Metric;
use App\Metrics\Metric\MetricInterface;
use App\Metrics\Metric\TypicalRangeAwareMetricInterface;
use App\Metrics\MetricDtoProvider;
use App\Metrics\MetricLocator;
use App\Metrics\Range\Dto\Range;
use App\Metrics\Range\Dto\Ranges;
use App\Metrics\Range\TypicalRangesProvider;
use App\Metrics\Renderer;
use App\Metrics\Renderer\Dto\AbstractDto;
use App\Metrics\Renderer\Dto\BasicDto;
use App\Metrics\Renderer\MetricRendererInterface;
use App\Metrics\Renderer\Model\Badge\BadgeStyle;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class MetricDtoProviderTest extends TestCase
{
    /**
     * A metric with no collected data returns null from getTypicalRangeValue() — the
     * interface declares it nullable. Badging must be skipped, not attempted.
     */
    public function testNoBadgeWhenTypicalRangeValueIsNull(): void
    {
        $dto = $this->getDto(typicalRangeValue: null);

        $this->assertNull($dto->badge);
    }

    public function testBadgeIsSetFromTheTypicalRangeValue(): void
    {
        $dto = $this->getDto(typicalRangeValue: 42.0);

        $this->assertSame('ok', $dto->badge?->label);
    }

    private function getDto(int|float|null $typicalRangeValue): AbstractDto
    {
        $metric = Metric::SystemCpuUsage;
        $dto = new BasicDto($metric);

        $service = new class ($typicalRangeValue) implements MetricInterface, TypicalRangeAwareMetricInterface {
            public function __construct(private readonly int|float|null $value) {}

            public function getMetric(): Metric
            {
                return Metric::SystemCpuUsage;
            }

            public function getTypicalRangeValue(array $options = []): int|float|null
            {
                return $this->value;
            }
        };

        $locator = $this->createMock(MetricLocator::class);
        $locator->method('get')->willReturn($service);

        $renderer = $this->createMock(MetricRendererInterface::class);
        $renderer->method('createDto')->willReturn($dto);

        $renderers = $this->createMock(ContainerInterface::class);
        $renderers->method('get')->willReturn($renderer);

        $ranges = $this->createMock(TypicalRangesProvider::class);
        $ranges->method('get')->willReturn(new Ranges([new Range(0, 100, BadgeStyle::SUCCESS, 'ok', 'Fine')]));

        $provider = new MetricDtoProvider($locator, $renderers, $ranges);
        $built = $provider->getDto($metric, Renderer::Basic);

        $this->assertInstanceOf(AbstractDto::class, $built);

        return $built;
    }
}
