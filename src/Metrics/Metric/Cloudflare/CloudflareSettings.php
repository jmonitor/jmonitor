<?php

declare(strict_types=1);

namespace App\Metrics\Metric\Cloudflare;

use App\Metrics\Dto\Bag\Cloudflare\ZoneBag;
use App\Metrics\Metric;
use App\Metrics\Metric\BasicMetricInterface;
use App\Metrics\Renderer\Dto\BasicDto;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

#[AsTaggedItem(Metric::CloudflareSettings->value)]
class CloudflareSettings implements BasicMetricInterface
{
    use CloudflareBlockTrait;

    public function getMetric(): Metric
    {
        return Metric::CloudflareSettings;
    }

    public function configureBasicDto(BasicDto $dto, array $options = []): void
    {
        $zone = $this->block($dto, 'zone');

        if ($zone instanceof ZoneBag && $this->subBlock($dto, $zone->hasSettings)) {
            $dto->setValue($zone);
        }
    }
}
