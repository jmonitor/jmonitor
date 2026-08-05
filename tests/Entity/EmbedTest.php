<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Chart\TimeRange;
use App\Entity\Embed;
use App\Metrics\Dto\Embed\GaugeEmbedOptions;
use App\Metrics\Dto\Embed\TimeSeriesEmbedOptions;
use App\Metrics\Dto\EmbedDto;
use App\Metrics\Metric;
use App\Metrics\Renderer;
use PHPUnit\Framework\TestCase;

class EmbedTest extends TestCase
{
    public function testTokenIsGeneratedAndUnguessable(): void
    {
        $a = new Embed();
        $b = new Embed();

        // 16 random bytes hex-encoded: 32 chars, 128 bits of entropy.
        $this->assertMatchesRegularExpression('/^[0-9a-f]{32}$/', $a->getToken());
        $this->assertNotSame($a->getToken(), $b->getToken());
    }

    public function testDtoRoundTrip(): void
    {
        // chart must already hold the renderer's default options: getDto() always fills it in via fromArray().
        $dto = new EmbedDto(Metric::SystemCpuUsage, Renderer::Gauge, true, chart: new GaugeEmbedOptions());

        $embed = new Embed()->setDto($dto);

        $this->assertEquals($dto, $embed->getDto());
    }

    public function testDtoCanBeUpdatedWithoutChangingTheToken(): void
    {
        $embed = new Embed()->setDto(new EmbedDto(Metric::SystemCpuUsage, Renderer::Gauge, false));
        $token = $embed->getToken();

        $newDto = new EmbedDto(Metric::SystemCpuUsage, Renderer::Line, true, chart: new TimeSeriesEmbedOptions(TimeRange::LAST_24_HOURS, 2.0));
        $embed->setDto($newDto);

        $this->assertEquals($newDto, $embed->getDto());
        $this->assertSame($token, $embed->getToken());
    }
}
