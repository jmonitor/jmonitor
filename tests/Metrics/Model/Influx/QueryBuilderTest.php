<?php

declare(strict_types=1);

namespace App\Tests\Metrics\Model\Influx;

use App\Chart\TimeRange;
use App\Metrics\Model\Influx\QueryBuilder;
use PHPUnit\Framework\TestCase;

class QueryBuilderTest extends TestCase
{
    // ----------------------------------------------------------------
    // Tests 1-8: existing behaviour (must pass before AND after refactor)
    // ----------------------------------------------------------------

    public function testBasicQueryFromMeasurementField(): void
    {
        $qb = new QueryBuilder('test');
        $qb->measurement('my_measurement')->field('my_field');

        $this->assertSame(
            'from(bucket: "test") |> filter(fn: (r) => r["_measurement"] == "my_measurement") |> filter(fn: (r) => r["_field"] == "my_field")',
            $qb->getQuery(),
        );
    }

    public function testMultipleFieldsProducesOrFilter(): void
    {
        $qb = new QueryBuilder('test');
        $qb->fields(['a', 'b']);

        $this->assertSame(
            'from(bucket: "test") |> filter(fn: (r) => r["_field"] == "a" or r["_field"] == "b")',
            $qb->getQuery(),
        );
    }

    public function testSingleElementFieldsShortcutMatchesField(): void
    {
        $qb1 = new QueryBuilder('test');
        $qb1->fields(['x']);

        $qb2 = new QueryBuilder('test');
        $qb2->field('x');

        $this->assertSame($qb2->getQuery(), $qb1->getQuery());
        $this->assertSame(
            'from(bucket: "test") |> filter(fn: (r) => r["_field"] == "x")',
            $qb1->getQuery(),
        );
    }

    public function testSingleTagProducesFilter(): void
    {
        $qb = new QueryBuilder('test');
        $qb->tag('host', 'srv1');

        $this->assertStringContainsString(
            'filter(fn: (r) => r["host"] == "srv1")',
            $qb->getQuery(),
        );
    }

    public function testTwoDifferentTagsAccumulateInInsertionOrder(): void
    {
        $qb = new QueryBuilder('test');
        $qb->tag('env', 'prod')->tag('zone', 'eu');

        $query = $qb->getQuery();

        $this->assertStringContainsString('filter(fn: (r) => r["env"] == "prod")', $query);
        $this->assertStringContainsString('filter(fn: (r) => r["zone"] == "eu")', $query);
        // insertion order: env must appear before zone
        $this->assertLessThan(
            (int) strpos($query, 'r["zone"]'),
            (int) strpos($query, 'r["env"]'),
        );
    }

    public function testDerivativeAloneProducesCorrectFragment(): void
    {
        $qb = new QueryBuilder('test');
        $qb->derivative();

        $this->assertSame(
            'from(bucket: "test") |> derivative(unit: 1s, nonNegative: false)',
            $qb->getQuery(),
        );
    }

    public function testAggregateWindowAloneProducesCorrectFragment(): void
    {
        $qb = new QueryBuilder('test');
        $qb->aggregateWindow(TimeRange::LAST_1_HOUR);

        // TimeRange::LAST_1_HOUR->asWindowPeriod() === '1m' (static match, deterministic)
        $this->assertSame(
            'from(bucket: "test") |> aggregateWindow(every: 1m, fn: mean, createEmpty: true)',
            $qb->getQuery(),
        );
    }

    public function testGetQueryEqualsToString(): void
    {
        $qb = new QueryBuilder('test');
        $qb->measurement('my_measurement')->field('my_field');

        $this->assertSame((string) $qb, $qb->getQuery());
    }

    // ----------------------------------------------------------------
    // Tests 9-15: order-independence (fail before refactor, pass after)
    // ----------------------------------------------------------------

    public function testSameTagNameCalledTwiceUsesLastValue(): void
    {
        $qb = new QueryBuilder('test');
        $qb->tag('env', 'prod')->tag('env', 'staging');

        $query = $qb->getQuery();

        $this->assertStringContainsString('filter(fn: (r) => r["env"] == "staging")', $query);
        $this->assertStringNotContainsString('"prod"', $query);
        $this->assertSame(1, substr_count($query, 'r["env"]'));
    }

    public function testAggregateWindowCalledAfterDerivativeStillAppearsLast(): void
    {
        $qb = new QueryBuilder('test');
        // reverse order: aggregateWindow first, then derivative
        $qb->aggregateWindow(TimeRange::LAST_1_HOUR)->derivative();

        $this->assertSame(
            'from(bucket: "test") |> derivative(unit: 1s, nonNegative: false) |> aggregateWindow(every: 1m, fn: mean, createEmpty: true)',
            $qb->getQuery(),
        );
    }

    public function testTagCalledAfterDerivativeStillAppearsBeforeDerivativeInOutput(): void
    {
        // Reproduces the FrankenPhpWorkerReqPerSec bug: derivative() called before tag()
        $qb = new QueryBuilder('test');
        $qb->derivative(nonNegative: true); // called first
        $qb->tag('worker', 0);             // int value — must be cast to "0" with quotes
        $qb->measurement('frankenphp_worker');
        $qb->field('worker_request_count');

        $this->assertSame(
            'from(bucket: "test") |> filter(fn: (r) => r["_measurement"] == "frankenphp_worker") |> filter(fn: (r) => r["_field"] == "worker_request_count") |> filter(fn: (r) => r["worker"] == "0") |> derivative(unit: 1s, nonNegative: true)',
            $qb->getQuery(),
        );
    }

    public function testDerivativeCalledTwiceUsesLastCall(): void
    {
        $qb = new QueryBuilder('test');
        $qb->derivative(unit: '1m', nonNegative: false);
        $qb->derivative(unit: '1s', nonNegative: true);

        $query = $qb->getQuery();

        $this->assertStringContainsString('derivative(unit: 1s, nonNegative: true)', $query);
        $this->assertSame(1, substr_count($query, 'derivative('));
    }

    public function testAggregateWindowCalledTwiceUsesLastCall(): void
    {
        $qb = new QueryBuilder('test');
        $qb->aggregateWindow(TimeRange::LAST_1_HOUR, 'mean'); // every: 1m
        $qb->aggregateWindow(TimeRange::LAST_5_MIN, 'last');  // every: 15s

        $query = $qb->getQuery();

        $this->assertStringContainsString('aggregateWindow(every: 15s, fn: last, createEmpty: true)', $query);
        $this->assertSame(1, substr_count($query, 'aggregateWindow('));
    }

    public function testRangeCalledTwiceUsesLastCall(): void
    {
        $qb = new QueryBuilder('test');
        $qb->range(TimeRange::LAST_1_HOUR);
        $qb->range(TimeRange::LAST_5_MIN);

        // Both ranges emit integer Unix timestamps — not comparable directly.
        // Assert the slot was overwritten (not appended): exactly one range() in output.
        $this->assertSame(1, substr_count($qb->getQuery(), 'range(start:'));
    }

    public function testMeasurementCalledTwiceUsesLastCall(): void
    {
        $qb = new QueryBuilder('test');
        $qb->measurement('first_measurement');
        $qb->measurement('second_measurement');

        $query = $qb->getQuery();

        $this->assertStringContainsString(
            'filter(fn: (r) => r["_measurement"] == "second_measurement")',
            $query,
        );
        $this->assertStringNotContainsString('first_measurement', $query);
        $this->assertSame(1, substr_count($query, 'r["_measurement"]'));
    }
}
