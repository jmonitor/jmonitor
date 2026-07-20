<?php

declare(strict_types=1);

namespace App\Tests\Demo\State;

use App\Demo\State\DemoState;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

class DemoStateTest extends TestCase
{
    public function testCounterIsMonotonic(): void
    {
        $state = new DemoState(new ArrayAdapter());

        $this->assertSame(5, $state->counter('c', 5));
        $this->assertSame(15, $state->counter('c', 10));
        // negative increment must not decrease the counter
        $this->assertSame(15, $state->counter('c', -100));
    }

    public function testWalkStaysWithinBounds(): void
    {
        $state = new DemoState(new ArrayAdapter());

        for ($i = 0; $i < 1000; $i++) {
            $v = $state->walk('w', 10.0, 20.0, 0.1);
            $this->assertGreaterThanOrEqual(10.0, $v);
            $this->assertLessThanOrEqual(20.0, $v);
        }
    }

    public function testSeasonalityRangeAndShape(): void
    {
        $state = new DemoState(new ArrayAdapter());

        for ($h = 0; $h < 24; $h++) {
            $s = $state->seasonality($h);
            $this->assertGreaterThanOrEqual(0.30, $s);
            $this->assertLessThanOrEqual(1.00, $s);
        }

        // night quieter than mid-afternoon
        $this->assertLessThan($state->seasonality(16), $state->seasonality(4));
    }

    public function testStateSurvivesAcrossInstancesViaCache(): void
    {
        $adapter = new ArrayAdapter();

        $first = new DemoState($adapter);
        $first->counter('c', 42);
        $first->persist();

        $second = new DemoState($adapter);
        // increment 0 returns the persisted running total
        $this->assertSame(42, $second->counter('c', 0));
    }
}
