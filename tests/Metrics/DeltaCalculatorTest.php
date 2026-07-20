<?php

declare(strict_types=1);

namespace App\Tests\Metrics;

use App\Entity\Project;
use App\Metrics\Consumer\Consumer;
use App\Metrics\DeltaCalculator;
use App\Metrics\Dto\MetricBagDto;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;

class DeltaCalculatorTest extends TestCase
{
    public function testGetDeltaLazyCalculation(): void
    {
        $cache = $this->createMock(CacheItemPoolInterface::class);
        $item = $this->createMock(CacheItemInterface::class);

        $calculator = new DeltaCalculator($cache);

        $project = $this->createMock(Project::class);
        $project->method('getId')->willReturn(1);

        $receivedAt = new \DateTimeImmutable('2026-02-10 10:00:00');

        $bag = MetricBagDto::create(
            $project,
            Consumer::PHP,
            1,
            [
                'fpm' => [
                    'accepted-conn' => 100,
                ],
            ],
            $receivedAt,
            false,
        );

        $cacheKey = 'DELTA_php_1_1';
        $cache->method('getItem')
            ->with($cacheKey)
            ->willReturn($item);

        $item->method('get')->willReturn([
            'timestamp' => $receivedAt->getTimestamp() - 10,
            'values' => [
                'fpm.accepted-conn' => 90,
            ],
        ]);

        $item->method('set')->willReturnSelf();
        $item->method('expiresAfter')->willReturnSelf();
        $cache->method('save')->willReturn(true);

        $deltaBag = $calculator->getDelta($bag);

        $this->assertNotNull($deltaBag);

        $this->assertEquals(10, $deltaBag->getValue('fpm.accepted-conn'));
        $this->assertEquals(1.0, $deltaBag->getPerSec('fpm.accepted-conn'));

        // Test that it handles negative deltas (reset)
        $bag2 = MetricBagDto::create(
            $project,
            Consumer::PHP,
            1,
            [
                'fpm' => [
                    'accepted-conn' => 50, // Lower than 100
                ],
            ],
            $receivedAt->modify('+10 seconds'),
            false,
        );

        // Reset property cache to avoid getting same result
        $calculator->reset();

        $item2 = $this->createMock(CacheItemInterface::class);
        $cache->method('getItem')->with($cacheKey)->willReturn($item2);
        $item2->method('get')->willReturn([
            'timestamp' => $receivedAt->getTimestamp(),
            'values' => [
                'fpm.accepted-conn' => 100,
            ],
        ]);

        $deltaBag2 = $calculator->getDelta($bag2);
        $this->assertNotNull($deltaBag2);
        $this->assertNull($deltaBag2->getValue('fpm.accepted-conn'));
    }
}
