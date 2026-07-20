<?php

declare(strict_types=1);

namespace App\Utils\Units;

class MilliSecond
{
    private const array UNITS = [
        'ms' => 1,
        's' => 1000,
        'm' => 60000,
        'h' => 3600000,
    ];

    private ?string $requestedUnit = null;

    /** @var array{float, string}|null */
    private ?array $converted = null;

    public function __construct(private readonly float|int $value) {}

    public function __clone()
    {
        $this->converted = null;
    }

    public function __toString(): string
    {
        return $this->format();
    }

    public function value(): float|int
    {
        return $this->value;
    }

    public function to(string $unit): self
    {
        if (!isset(self::UNITS[$unit])) {
            throw new \InvalidArgumentException(\sprintf('"%s" is an invalid time unit.', $unit));
        }

        if ($unit === $this->requestedUnit) {
            return $this;
        }

        $clone = clone $this;
        $clone->requestedUnit = $unit;

        return $clone;
    }

    public function getFinalValue(): float
    {
        return $this->convert()[0];
    }

    public function getUnit(): string
    {
        return $this->convert()[1];
    }

    public function format(?string $template = null, bool $includeHtml = true): string
    {
        [$quantity, $unit] = $this->convert();

        if ($template) {
            if ($includeHtml) {
                $unit = '<span class="unit">' . $unit . '</span>';
            }
            return \sprintf($template, $quantity, $unit);
        }

        if ($includeHtml) {
            $template = $quantity === \floor($quantity) ? '%d <span class="unit">%s</span>' : '%.2f <span class="unit">%s</span>';
        } else {
            $template = $quantity === \floor($quantity) ? '%d %s' : '%.2f %s';
        }

        return \sprintf($template, $quantity, $unit);
    }

    /**
     * @return array{float, string} [value, unit]
     */
    private function convert(): array
    {
        if (null !== $this->converted) {
            return $this->converted;
        }

        if ($this->requestedUnit) {
            $factor = self::UNITS[$this->requestedUnit];
            return $this->converted = [(float) ($this->value / $factor), $this->requestedUnit];
        }

        $units = \array_reverse(self::UNITS, true);
        foreach ($units as $unit => $factor) {
            if ($this->value >= $factor) {
                return $this->converted = [(float) ($this->value / $factor), $unit];
            }
        }

        return $this->converted = [(float) $this->value, 'ms'];
    }
}
