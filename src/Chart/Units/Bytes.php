<?php

declare(strict_types=1);

namespace App\Chart\Units;

use Zenstruck\Bytes as ZenstruckBytes;

class Bytes
{
    private const int DECIMAL = 1000;
    private const int BINARY = 1024;

    private const array DECIMAL_UNITS = [
        'b' => 'B',
        'kb' => 'kB',
        'mb' => 'MB',
        'gb' => 'GB',
        'tb' => 'TB',
        'pb' => 'PB',
        'eb' => 'EB',
        'zb' => 'ZB',
        'yb' => 'YB',
    ];

    private const array BINARY_UNITS = [
        'b' => 'B',
        'kib' => 'KiB',
        'mib' => 'MiB',
        'gib' => 'GiB',
        'tib' => 'TiB',
        'pib' => 'PiB',
        'eib' => 'EiB',
        'zib' => 'ZiB',
        'yib' => 'YiB',
    ];

    private const array UNIT_MAP = [
        'B' => [0, self::DECIMAL],
        'kB' => [1, self::DECIMAL],
        'KiB' => [1, self::BINARY],
        'MB' => [2, self::DECIMAL],
        'MiB' => [2, self::BINARY],
        'GB' => [3, self::DECIMAL],
        'GiB' => [3, self::BINARY],
        'TB' => [4, self::DECIMAL],
        'TiB' => [4, self::BINARY],
        'PB' => [5, self::DECIMAL],
        'PiB' => [5, self::BINARY],
        'EB' => [6, self::DECIMAL],
        'EiB' => [6, self::BINARY],
        'ZB' => [7, self::DECIMAL],
        'ZiB' => [7, self::BINARY],
        'YB' => [8, self::DECIMAL],
        'YiB' => [8, self::BINARY],
    ];

    private const array ALTERNATE_MAP = [
        'k' => 'kB',
        'ki' => 'KiB',
        'm' => 'MB',
        'mi' => 'MiB',
        'g' => 'GB',
        'gi' => 'GiB',
        't' => 'TB',
        'ti' => 'TiB',
        'p' => 'PB',
        'pi' => 'PiB',
        'e' => 'EB',
        'ei' => 'EiB',
        'z' => 'ZB',
        'zi' => 'ZiB',
        'y' => 'YB',
        'yi' => 'YiB',
    ];

    private int $system = self::BINARY;
    private ?string $requestedUnit = null;
    private bool $isPerSecond = false;

    /** @var array{float, string, int}|null */
    private ?array $converted = null;

    public function __construct(private int $value) {}

    public function __clone()
    {
        $this->converted = null;
    }

    public function __toString(): string
    {
        return $this->format();
    }

    public static function parse(string|int|float|ZenstruckBytes|self $value): self
    {
        if ($value instanceof self) {
            return $value;
        }

        if ($value instanceof ZenstruckBytes) {
            return new self($value->value());
        }

        return new self(ZenstruckBytes::parse($value)->value());
    }

    public function perSecond(): self
    {
        $clone = clone $this;
        $clone->isPerSecond = true;

        return $clone;
    }

    public function isPerSecond(): bool
    {
        return $this->isPerSecond;
    }

    public function value(): int
    {
        return $this->value;
    }

    public function asBinary(): self
    {
        if (self::BINARY === $this->system) {
            return $this;
        }

        $clone = clone $this;
        $clone->system = self::BINARY;

        return $clone;
    }

    public function asDecimal(): self
    {
        if (self::DECIMAL === $this->system) {
            return $this;
        }

        $clone = clone $this;
        $clone->system = self::DECIMAL;

        return $clone;
    }

    public function to(string $unit): self
    {
        $normalized = self::normalize($unit);

        if ($normalized === $this->requestedUnit) {
            return $this;
        }

        $clone = clone $this;
        $clone->requestedUnit = $normalized;

        return $clone;
    }

    public function roundFinalValue(int $precision = 0): float
    {
        return \round($this->getFinalValue(), $precision);
    }

    public function getFinalValue(): float
    {
        return $this->convert()[0];
    }

    public function getUnit(): string
    {
        return $this->isPerSecond ? $this->convert()[1] . '/s' : $this->convert()[1];
    }

    public function getFactor(): int
    {
        return $this->convert()[2];
    }

    public function format(?string $template = null): string
    {
        [$quantity, $unit] = $this->convert();

        if ($this->isPerSecond) {
            $unit .= '/s';
        }

        if ($template) {
            $unit = '<span class="unit">' . $unit . '</span>';

            return \sprintf($template, $quantity, $unit);
        }

        $template = $quantity === \floor($quantity) ? '%d <span class="unit">%s</span>' : '%.2f <span class="unit">%s</span>';

        return \sprintf($template, $quantity, $unit);
    }

    /**
     * @return array{float, string, int} [value, unit, factor]
     */
    private function convert(): array
    {
        if (null !== $this->converted) {
            return $this->converted;
        }

        if ($this->requestedUnit) {
            [$multiplier, $base] = self::UNIT_MAP[$this->requestedUnit];
            $factor = $base ** $multiplier;

            return $this->converted = [(float) ($this->value / $factor), $this->requestedUnit, $factor];
        }

        $i = 0;
        $units = \array_values(self::DECIMAL === $this->system ? self::DECIMAL_UNITS : self::BINARY_UNITS);
        $quantity = (float) $this->value;

        while (($quantity / $this->system) >= 1 && $i < (\count($units) - 1)) {
            $quantity /= $this->system;
            ++$i;
        }

        $factor = $this->system ** $i;

        return $this->converted = [$quantity, $units[$i], $factor];
    }

    private static function normalize(string $units): string
    {
        $lower = \mb_strtolower($units);

        return self::BINARY_UNITS[$lower] ?? self::DECIMAL_UNITS[$lower] ?? self::ALTERNATE_MAP[$lower] ?? throw new \InvalidArgumentException(\sprintf('"%s" is an invalid informational unit.', $units));
    }
}
