<?php

declare(strict_types=1);

namespace App\Metrics\Metric\Cloudflare;

use App\Metrics\Metric;
use App\Metrics\Metric\BasicMetricInterface;
use App\Metrics\Renderer\Dto\BasicDto;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

#[AsTaggedItem(Metric::CloudflareToday->value)]
class CloudflareToday implements BasicMetricInterface
{
    use CloudflareBlockTrait;

    public function getMetric(): Metric
    {
        return Metric::CloudflareToday;
    }

    public function configureBasicDto(BasicDto $dto, array $options = []): void
    {
        $dto->setValue($this->block($dto, 'today'));
    }
}
