<?php

declare(strict_types=1);

namespace App\Alerting\Config;

use App\Bridge\Eol\Dto\Cycle;
use App\Metrics\Dto\Bag;

class OutdatedVersionConfig extends Bag implements OutdatedVersionConfigInterface
{
    public ?OutdatedVersion $threshold {
        get => $this->get('threshold') ? OutdatedVersion::from($this->get('threshold')) : null;
        set {
            $this->parameters['threshold'] = $value->value;
        }
    }

    public function getDescription(): ?string
    {
        return $this->threshold?->label();
    }

    public function isSatisfiedBy(Cycle $cycle): bool
    {
        return $this->threshold->isReachedBy($cycle);
    }
}
