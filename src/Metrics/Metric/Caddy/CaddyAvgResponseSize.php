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
use Twig\Markup;

#[AsTaggedItem(Metric::CaddyAvgResponseSize->value)]
class CaddyAvgResponseSize implements BasicMetricInterface, OptionsAwareMetricInterface
{
    use BagAwareTrait;

    public function getMetric(): Metric
    {
        return Metric::CaddyAvgResponseSize;
    }

    public function configureBasicDto(BasicDto $dto, array $options = []): void
    {
        $handler = $options['handler'];

        $value = $this->getCaddyBag()?->avgResponseSizeBytes->getInt($handler);

        $dto->setFormattedValue($value !== null ? new Markup(Bytes::parse($value)->format(), 'UTF-8') : null);
    }

    public function configureOptions(OptionsResolver $optionsResolver): void
    {
        $optionsResolver->setRequired('handler');
        $optionsResolver->setAllowedValues('handler', ['php', 'file_server']);
    }
}
