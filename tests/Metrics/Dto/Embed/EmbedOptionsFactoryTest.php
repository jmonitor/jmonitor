<?php

declare(strict_types=1);

namespace App\Tests\Metrics\Dto\Embed;

use App\Form\Embed\GaugeEmbedOptionsType;
use App\Form\Embed\TimeSeriesEmbedOptionsType;
use App\Metrics\Dto\Embed\EmbedOptionsFactory;
use App\Metrics\Dto\Embed\GaugeEmbedOptions;
use App\Metrics\Dto\Embed\TimeSeriesEmbedOptions;
use App\Metrics\Renderer;
use PHPUnit\Framework\TestCase;

class EmbedOptionsFactoryTest extends TestCase
{
    public function testLineAndBarHydrateTimeSeriesOptions(): void
    {
        $this->assertInstanceOf(TimeSeriesEmbedOptions::class, EmbedOptionsFactory::hydrate(Renderer::Line, []));
        $this->assertInstanceOf(TimeSeriesEmbedOptions::class, EmbedOptionsFactory::hydrate(Renderer::Bar, []));
    }

    public function testGaugeHydratesGaugeOptions(): void
    {
        $this->assertInstanceOf(GaugeEmbedOptions::class, EmbedOptionsFactory::hydrate(Renderer::Gauge, ['aspectRatio' => 1.2]));
    }

    public function testRenderersWithoutOptionsHydrateToNull(): void
    {
        $this->assertNull(EmbedOptionsFactory::hydrate(Renderer::Basic, []));
        $this->assertNull(EmbedOptionsFactory::hydrate(Renderer::ConsumerValue, []));
        $this->assertNull(EmbedOptionsFactory::hydrate(null, []));
    }

    public function testCreateEmptyReturnsAFullyNullObject(): void
    {
        $this->assertEquals(new TimeSeriesEmbedOptions(), EmbedOptionsFactory::createEmpty(Renderer::Line));
        $this->assertNull(EmbedOptionsFactory::createEmpty(Renderer::Basic));
    }

    public function testFormTypeFollowsTheRenderer(): void
    {
        $this->assertSame(TimeSeriesEmbedOptionsType::class, EmbedOptionsFactory::formType(Renderer::Line));
        $this->assertSame(TimeSeriesEmbedOptionsType::class, EmbedOptionsFactory::formType(Renderer::Bar));
        $this->assertSame(GaugeEmbedOptionsType::class, EmbedOptionsFactory::formType(Renderer::Gauge));
        $this->assertNull(EmbedOptionsFactory::formType(Renderer::Basic));
        $this->assertNull(EmbedOptionsFactory::formType(null));
    }
}
