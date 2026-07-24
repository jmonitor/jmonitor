<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Chart\TimeRange;
use App\Entity\Embed;
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
        $dto = new EmbedDto(Metric::SystemCpuUsage, Renderer::Gauge, null, true, null);

        $embed = new Embed()->setDto($dto);

        $this->assertEquals($dto, $embed->getDto());
    }

    public function testDtoCanBeUpdatedWithoutChangingTheToken(): void
    {
        $embed = new Embed()->setDto(new EmbedDto(Metric::SystemCpuUsage, Renderer::Gauge, null, false, null));
        $token = $embed->getToken();

        $newDto = new EmbedDto(Metric::SystemCpuUsage, Renderer::Line, TimeRange::LAST_24_HOURS, true, ['aspectRatio' => 2.0]);
        $embed->setDto($newDto);

        $this->assertEquals($newDto, $embed->getDto());
        $this->assertSame($token, $embed->getToken());
    }
}
