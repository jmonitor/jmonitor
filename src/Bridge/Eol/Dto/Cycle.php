<?php

declare(strict_types=1);

namespace App\Bridge\Eol\Dto;

use DateTimeImmutable;

readonly class Cycle
{
    public function __construct(
        public private(set) string $cycle,
        public private(set) DateTimeImmutable $releaseDate,
        public private(set) DateTimeImmutable|bool $eol,
        public private(set) string $latest,
        public private(set) DateTimeImmutable $latestReleaseDate,
        public private(set) bool $lts,
        public private(set) DateTimeImmutable|bool|null $support,
    ) {}

    /**
     * e.g.:
     * {
     * "cycle": "8.5",
     * "releaseDate": "2025-11-20",
     * "eol": "2029-12-31",
     * "latest": "8.5.0",
     * "latestReleaseDate": "2025-11-20",
     * "lts": false, (may contain a date - not handled today -> considered true)
     * "support": "2027-12-31"
     * },
     *
     */
    public static function fromEolResponse(array $response): self
    {
        if (isset($response['support'])) {
            $support = is_bool($response['support']) ? $response['support'] : DateTimeImmutable::createFromFormat('Y-m-d', $response['support']);
        }

        return new self(
            $response['cycle'],
            DateTimeImmutable::createFromFormat('Y-m-d', $response['releaseDate']),
            is_bool($response['eol']) ? $response['eol'] : DateTimeImmutable::createFromFormat('Y-m-d', $response['eol']),
            $response['latest'],
            DateTimeImmutable::createFromFormat('Y-m-d', $response['latestReleaseDate']),
            (bool) $response['lts'],
            $support ?? null,
        );
    }

    public function isActive(): bool
    {
        if ($this->support !== null) {
            return $this->hasSupport();
        }

        return !$this->isEol();
    }

    public function isSecurityFixOnly(): bool
    {
        return !$this->isActive() && !$this->isEol();

    }

    public function isEol(): bool
    {
        return is_bool($this->eol) ? $this->eol : $this->eol < new DateTimeImmutable();
    }

    public function eolDate(): ?DateTimeImmutable
    {
        if ($this->eol instanceof DateTimeImmutable) {
            return $this->eol;
        }

        return null;
    }

    public function isLts(): bool
    {
        return $this->lts;
    }

    public function hasSupport(): bool
    {
        if ($this->support === null) {
            return false;
        }

        return is_bool($this->support) ? $this->support : $this->support > new DateTimeImmutable();
    }
}
