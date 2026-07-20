<?php

declare(strict_types=1);

namespace App\Alerting;

/**
 */
enum Unit
{
    case Percent;
    case Byte;
    case BytePerSec;
    case Millisecond;
}
