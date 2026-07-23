<?php

declare(strict_types=1);

namespace App\Tests\Entity;

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

    public function testRevoke(): void
    {
        $embed = new Embed();

        $this->assertFalse($embed->isRevoked());

        $embed->revoke();

        $this->assertTrue($embed->isRevoked());
    }
}
