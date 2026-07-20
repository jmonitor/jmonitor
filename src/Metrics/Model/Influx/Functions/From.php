<?php

declare(strict_types=1);

namespace App\Metrics\Model\Influx\Functions;

class From implements \Stringable
{
    public function __construct(private readonly string $bucketName) {}

    public function __toString(): string
    {
        return \sprintf('from(bucket: "%s")', $this->bucketName);
    }
}
