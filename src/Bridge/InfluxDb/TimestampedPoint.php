<?php

declare(strict_types=1);

namespace App\Bridge\InfluxDb;

use InfluxDB2\Model\WritePrecision;
use InfluxDB2\Point;

/**
 * A point written at the time its source reports, not at the time the metrics were received.
 */
final class TimestampedPoint extends Point
{
    /**
     * @param array<string, mixed> $fields
     * @param array<string, string> $tags
     */
    public function __construct(string $measurement, int $timestamp, array $fields = [], array $tags = [])
    {
        parent::__construct($measurement, $tags, $fields, $timestamp, WritePrecision::S);
    }
}
