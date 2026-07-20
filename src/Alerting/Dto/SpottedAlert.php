<?php

declare(strict_types=1);

namespace App\Alerting\Dto;

use App\Entity\Alert;

// TODO an interface could be nice someday (e.g. to better handle the description in the email)
readonly class SpottedAlert
{
    /**
     * @param list<string> $details extra detail lines, rendered by the per-metric email partial
     */
    public function __construct(
        private Alert $alert,
        private int|float|null $value = null,
        private array $details = [],
    ) {}

    public function getAlert(): Alert
    {
        return $this->alert;
    }

    public function getValue(): int|float|null
    {
        return $this->value;
    }

    /**
     * @return list<string>
     */
    public function getDetails(): array
    {
        return $this->details;
    }
}
