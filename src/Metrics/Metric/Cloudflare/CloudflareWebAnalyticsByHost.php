<?php

declare(strict_types=1);

namespace App\Metrics\Metric\Cloudflare;

use App\Metrics\Dto\Bag\Cloudflare\WebAnalyticsBag;
use App\Metrics\Dto\Bag\Cloudflare\WindowBag;
use App\Metrics\Metric;
use App\Metrics\Metric\BasicMetricInterface;
use App\Metrics\Renderer\Dto\BasicDto;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

#[AsTaggedItem(Metric::CloudflareWebAnalyticsByHost->value)]
class CloudflareWebAnalyticsByHost implements BasicMetricInterface
{
    use CloudflareBlockTrait;

    public function getMetric(): Metric
    {
        return Metric::CloudflareWebAnalyticsByHost;
    }

    public function configureBasicDto(BasicDto $dto, array $options = []): void
    {
        $analytics = $this->block($dto, 'web_analytics');

        if ($analytics instanceof WebAnalyticsBag) {
            $dto
                ->setValue(WindowBag::top($analytics->byHost))
                ->setContext(['total' => array_sum($analytics->byHost), 'labels' => [], 'styles' => []])
            ;
        }
    }
}
