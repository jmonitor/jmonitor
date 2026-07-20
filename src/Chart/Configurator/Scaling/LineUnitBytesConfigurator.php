<?php

declare(strict_types=1);

namespace App\Chart\Configurator\Scaling;

use App\Chart\Units\Bytes;
use App\Chart\Units\Unit;

readonly class LineUnitBytesConfigurator extends AbstractUnitScalingConfigurator
{
    public function supportsUnit(Unit $unit): bool
    {
        return $unit === Unit::Byte;
    }

    protected function computeScaleFactor(int $maxValue): float
    {
        return Bytes::parse($maxValue)->getFactor();
    }
}
