<?php

declare(strict_types=1);

namespace App\Metrics\Dto\Bag\Cloudflare;

use App\Metrics\Dto\Bag;

class R2BucketBag extends Bag
{
    public string $name {
        get => (string) $this->get('name', '');
    }

    public ?string $storageClass {
        get => $this->get('storage_class');
    }

    public ?\DateTimeImmutable $createdAt {
        get => ($t = $this->getInt('created_at')) === null ? null : new \DateTimeImmutable('@' . $t);
    }

    public int $objectCount {
        get => $this->getInt('object_count', 0) ?? 0;
    }

    public int $payloadBytes {
        get => $this->getInt('payload_bytes', 0) ?? 0;
    }

    public int $metadataBytes {
        get => $this->getInt('metadata_bytes', 0) ?? 0;
    }

    public int $classAOperations {
        get => $this->getInt('class_a_operations', 0) ?? 0;
    }

    public int $classBOperations {
        get => $this->getInt('class_b_operations', 0) ?? 0;
    }

    public int $uploadBytes {
        get => $this->getInt('upload_bytes', 0) ?? 0;
    }

    public int $downloadBytes {
        get => $this->getInt('download_bytes', 0) ?? 0;
    }
}
