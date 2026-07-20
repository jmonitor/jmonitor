<?php

declare(strict_types=1);

namespace App\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;
use Zenstruck\Bytes;

/**
 * @implements DataTransformerInterface<int, array{value: float|null, unit: string}>
 */
readonly class ByteUnitTransformer implements DataTransformerInterface
{
    public function __construct(
        private bool $useBinary = true,
    ) {}

    public function transform(mixed $value): array
    {
        if (null === $value) {
            return ['value' => null, 'unit' => 'MB'];
        }

        $bytes = new Bytes($value);

        if ($this->useBinary) {
            $bytes = $bytes->asBinary();
        } else {
            $bytes = $bytes->asDecimal();
        }

        $formatted = $bytes->format();

        [$val, $unit] = explode(' ', $formatted);

        return [
            'value' => round((float) $val, 2),
            'unit' => $unit,
        ];
    }

    public function reverseTransform(mixed $value): ?int
    {
        if (($value['value'] ?? null) === null) {
            return null;
        }

        $bytes = Bytes::parse($value['value'] . $value['unit']);

        if ($this->useBinary) {
            $bytes = $bytes->asBinary();
        } else {
            $bytes = $bytes->asDecimal();
        }

        return $bytes->value();
    }
}
