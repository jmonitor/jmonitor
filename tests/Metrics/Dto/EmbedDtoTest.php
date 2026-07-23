<?php

declare(strict_types=1);

namespace App\Tests\Metrics\Dto;

use App\Chart\TimeRange;
use App\Metrics\Dto\EmbedDto;
use App\Metrics\Metric;
use App\Metrics\Renderer;
use PHPUnit\Framework\TestCase;

class EmbedDtoTest extends TestCase
{
    public function testFromArrayRoundTripsJsonSerialize(): void
    {
        $dto = new EmbedDto(Metric::SystemCpuUsage, Renderer::Line, TimeRange::LAST_1_HOUR, true, ['aspectRatio' => 2.0]);

        $this->assertEquals($dto, EmbedDto::fromArray($dto->jsonSerialize()));
    }

    public function testFromArrayWithOnlyMetric(): void
    {
        $dto = EmbedDto::fromArray(['m' => 'system.cpu_usage']);

        $this->assertSame(Metric::SystemCpuUsage, $dto->metric);
        $this->assertNull($dto->renderer);
        $this->assertNull($dto->range);
        $this->assertFalse($dto->autoRefresh);
        $this->assertNull($dto->chartConfig);
    }

    public function testFromArrayCastsNumericAspectRatioString(): void
    {
        $dto = EmbedDto::fromArray(['m' => 'system.cpu_usage', 'cc' => ['aspectRatio' => '1.5']]);

        $this->assertSame(1.5, $dto->chartConfig['aspectRatio']);
    }

    public function testFromArrayThrowsOnUnknownMetric(): void
    {
        $this->expectException(\ValueError::class);

        EmbedDto::fromArray(['m' => 'does.not_exist']);
    }
}
