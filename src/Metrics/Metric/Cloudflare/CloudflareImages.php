<?php

declare(strict_types=1);

namespace App\Metrics\Metric\Cloudflare;

use App\Metrics\Dto\Bag\Cloudflare\ImagesBag;
use App\Metrics\Metric;
use App\Metrics\Metric\BasicMetricInterface;
use App\Metrics\Renderer\Dto\BasicDto;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

#[AsTaggedItem(Metric::CloudflareImages->value)]
class CloudflareImages implements BasicMetricInterface
{
    use CloudflareBlockTrait;

    public function getMetric(): Metric
    {
        return Metric::CloudflareImages;
    }

    public function configureBasicDto(BasicDto $dto, array $options = []): void
    {
        $images = $this->block($dto, 'images');

        if ($images instanceof ImagesBag) {
            $quotas = [Quota::count('Unique transformations', $images->uniqueTransformations, ImagesBag::FREE_UNIQUE_TRANSFORMATIONS)];

            if ($images->storedQuota !== null && $images->storedQuota > 0) {
                $quotas[] = Quota::count('Stored images', $images->stored, $images->storedQuota);
            }

            $dto
                ->setValue($images)
                ->setContext(['quotas' => $quotas])
            ;
        }
    }
}
