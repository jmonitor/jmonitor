<?php

declare(strict_types=1);

namespace App\Metrics\Metric\Cloudflare;

use App\Metrics\Dto\Bag\Cloudflare\R2Bag;
use App\Metrics\Metric;
use App\Metrics\Metric\BasicMetricInterface;
use App\Metrics\Renderer\Dto\BasicDto;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

#[AsTaggedItem(Metric::CloudflareR2->value)]
class CloudflareR2 implements BasicMetricInterface
{
    use CloudflareBlockTrait;

    public function getMetric(): Metric
    {
        return Metric::CloudflareR2;
    }

    public function configureBasicDto(BasicDto $dto, array $options = []): void
    {
        $r2 = $this->block($dto, 'r2');

        if ($r2 instanceof R2Bag) {
            $dto
                ->setValue($r2)
                ->setContext(['quotas' => [
                    Quota::bytes('Storage', $r2->storageBytes(), R2Bag::FREE_STORAGE_BYTES),
                    Quota::count('Class A operations', $r2->classAOperations, R2Bag::FREE_CLASS_A_OPERATIONS),
                    Quota::count('Class B operations', $r2->classBOperations, R2Bag::FREE_CLASS_B_OPERATIONS),
                ]])
            ;
        }
    }
}
