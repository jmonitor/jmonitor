<?php

declare(strict_types=1);

namespace App\Tests\Dev;

use App\Dev\PhpCollector;
use Jmonitor\Collector\Php\PhpCollector as JmonitorPhpCollector;
use PHPUnit\Framework\TestCase;

final class PhpCollectorTest extends TestCase
{
    public function testCollectPassesRealMetricsThroughUntouched(): void
    {
        $real = ['opcache' => ['enabled' => true], 'memory' => ['usage' => 123]];

        $decorated = $this->createMock(JmonitorPhpCollector::class);
        $decorated->method('collect')->willReturn($real);

        $collector = new PhpCollector($decorated);

        // The template is a transparent pass-through by default: no synthetic FPM section.
        self::assertSame($real, $collector->collect());
        self::assertArrayNotHasKey('fpm', $collector->collect());
    }
}
