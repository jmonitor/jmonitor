<?php

declare(strict_types=1);

namespace App\Form\DataTransformer;

use App\Utils\Units\MilliSecond;
use Symfony\Component\Form\DataTransformerInterface;

/**
 * @implements DataTransformerInterface<int|float, array{value: float|null, unit: string}>
 */
readonly class MsUnitTransformer implements DataTransformerInterface
{
    public function transform(mixed $value): array
    {
        if (null === $value) {
            return ['value' => null, 'unit' => 'ms'];
        }

        $ms = new MilliSecond($value);

        return [
            'value' => $ms->getFinalValue(),
            'unit' => $ms->getUnit(),
        ];
    }

    public function reverseTransform(mixed $value): ?float
    {
        if (!isset($value['value'])) {
            return null;
        }

        $unit = $value['unit'];

        // MilliSecond constructor takes value in ms by default.
        // If we have 1 and unit 's', we want 1000.

        $factor = match ($unit) {
            's' => 1000,
            'm' => 60000,
            'h' => 3600000,
            default => 1,
        };

        return (float) ($value['value'] * $factor);
    }
}
