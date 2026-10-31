<?php

declare(strict_types=1);

namespace App\Tests\Bridge\InfluxDb;

use App\Bridge\InfluxDb\TimestampedPoint;
use PHPUnit\Framework\TestCase;

final class TimestampedPointTest extends TestCase
{
    public function testLineProtocolCarriesTheGivenSecond(): void
    {
        $point = new TimestampedPoint('cloudflare', 1761917460, ['requests' => 13]);

        $this->assertSame('cloudflare requests=13i 1761917460', $point->toLineProtocol());
    }
}
