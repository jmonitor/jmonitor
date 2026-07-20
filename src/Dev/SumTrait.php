<?php

declare(strict_types=1);

namespace App\Dev;

trait SumTrait
{
    private array $numbers = [];

    public function sum(string $name, int|float $value): int|float|null
    {
        $this->numbers[$name] ??= 0;
        $this->numbers[$name] += $value;

        return $this->numbers[$name];
    }
}
