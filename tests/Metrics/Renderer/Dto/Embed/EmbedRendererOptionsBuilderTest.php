<?php

declare(strict_types=1);

namespace App\Tests\Metrics\Renderer\Dto\Embed;

use App\Metrics\Dto\EmbedDto;
use App\Metrics\Metric;
use App\Metrics\Renderer;
use App\Metrics\Renderer\Dto\Embed\EmbedRendererOptionsBuilder;
use PHPUnit\Framework\TestCase;

class EmbedRendererOptionsBuilderTest extends TestCase
{
    public function testStringAspectRatioFromQueryStringDoesNotCrash(): void
    {
        // MapQueryString delivers cc values as strings; strict_types used to make this a TypeError 500.
        $dto = new EmbedDto(Metric::SystemCpuUsage, Renderer::Line, null, false, ['aspectRatio' => '1.5']);

        $builder = EmbedRendererOptionsBuilder::fromEmbedDto($dto);

        $this->assertInstanceOf(EmbedRendererOptionsBuilder::class, $builder);
    }

    public function testNonNumericAspectRatioIsIgnored(): void
    {
        $dto = new EmbedDto(Metric::SystemCpuUsage, Renderer::Line, null, false, ['aspectRatio' => 'abc']);

        $builder = EmbedRendererOptionsBuilder::fromEmbedDto($dto);

        $this->assertInstanceOf(EmbedRendererOptionsBuilder::class, $builder);
    }
}
