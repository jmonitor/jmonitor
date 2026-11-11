<?php

declare(strict_types=1);

namespace App\Metrics\Dto\Bag\Cloudflare;

use App\Metrics\Dto\Bag;

class R2Bag extends Bag
{
    use MonthlyQuotaTrait;

    public const int FREE_STORAGE_BYTES = 10 * 1024 ** 3;
    public const int FREE_CLASS_A_OPERATIONS = 1_000_000;
    public const int FREE_CLASS_B_OPERATIONS = 10_000_000;

    public int $classAOperations {
        get => $this->getInt('class_a_operations', 0) ?? 0;
    }

    public int $classBOperations {
        get => $this->getInt('class_b_operations', 0) ?? 0;
    }

    public int $uploadBytes {
        get => $this->getInt('upload_bytes', 0) ?? 0;
    }

    /**
     * @var R2BucketBag[]
     */
    public array $buckets {
        get => $this->buckets ??= array_values(array_map(
            fn(array $bucket): R2BucketBag => new R2BucketBag($bucket),
            array_filter(is_array($this->get('buckets')) ? $this->all('buckets') : [], 'is_array'),
        ));
    }

    public function storageBytes(): int
    {
        return array_sum(array_map(fn(R2BucketBag $bucket): int => $bucket->payloadBytes + $bucket->metadataBytes, $this->buckets));
    }
}
