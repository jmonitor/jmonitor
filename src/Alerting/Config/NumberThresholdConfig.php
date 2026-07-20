<?php

declare(strict_types=1);

namespace App\Alerting\Config;

use App\Entity\Enums\AlertType;
use App\Metrics\Dto\Bag;

class NumberThresholdConfig extends Bag implements ThresholdConfigInterface
{
    // used in forms, so it must be nullable
    public int|float|null $threshold {
        get => $this->get('threshold');
        set {
            $this->parameters['threshold'] = $value;
        }
    }

    public function getDescription(): ?string
    {
        return (string) $this->threshold;
    }

    /**
     * Tests whether the given value should trigger the alert (against the configured value in $this)
     */
    public function isSatisfiedBy(float|int $value, AlertType $type): bool
    {
        return match ($type) {
            AlertType::MinValueThreshold,
            AlertType::MinPercentThreshold => $value <= $this->threshold,
            AlertType::MaxValueThreshold,
            AlertType::MaxPercentThreshold => $value >= $this->threshold,
            default => throw new \InvalidArgumentException('Alert type not supported'),
        };
    }
}
