<?php

declare(strict_types=1);

namespace App\Metrics\Metric\Caddy;

use App\Chart\Units\Bytes;
use App\Metrics\BagAwareTrait;
use App\Metrics\Metric;
use App\Metrics\Metric\BasicMetricInterface;
use App\Metrics\Metric\OptionsAwareMetricInterface;
use App\Metrics\Renderer\Dto\BasicDto;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;
use Symfony\Component\OptionsResolver\OptionsResolver;

#[AsTaggedItem(Metric::CaddyAvgRequestSize->value)]
class CaddyAvgRequestSize implements BasicMetricInterface, OptionsAwareMetricInterface
{
    use BagAwareTrait;

    public function getMetric(): Metric
    {
        return Metric::CaddyAvgRequestSize;
    }

    public function configureBasicDto(BasicDto $dto, array $options = []): void
    {
        $handler = $options['handler'];

        $value = $this->getCaddyBag()?->avgRequestSizeBytes->getInt($handler);
        $value = $value !== null ? Bytes::parse($value) : null;

        $dto
            ->setValue($value?->format())
        ;
    }

    public function configureOptions(OptionsResolver $optionsResolver): void
    {
        $optionsResolver->setRequired('handler');
        $optionsResolver->setAllowedValues('handler', ['php', 'file_server']);
    }
}
