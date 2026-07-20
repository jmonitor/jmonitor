<?php

declare(strict_types=1);

namespace App\Metrics\Metric\Caddy;

use App\Metrics\BagAwareTrait;
use App\Metrics\Metric;
use App\Metrics\Metric\GaugeMetricInterface;
use App\Metrics\Metric\OptionsAwareMetricInterface;
use App\Metrics\Renderer\Dto\GaugeDto;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;
use Symfony\Component\OptionsResolver\OptionsResolver;

#[AsTaggedItem(Metric::CaddyRequestsUnder250->value)]
class CaddyRequestUnder250Ms implements GaugeMetricInterface, OptionsAwareMetricInterface
{
    use BagAwareTrait;

    public function getMetric(): Metric
    {
        return Metric::CaddyRequestsUnder250;
    }

    public function configureGauge(GaugeDto $gauge, array $options = []): void
    {
        $handler = $options['handler'];

        $gauge
            ->setValue($this->getCaddyBag()?->responseDurationSecondsBucketLe250msPecents->getFloat($handler), 1)
            ->setContext([
                'nb250' => $this->getCaddyBag()?->responseDurationSecondsBucketLe250msDelta->getInt($handler),
                'nbTotal' => $this->getCaddyBag()?->requestsTotalDelta->getInt($handler),
            ])
        ;
    }

    public function getTypicalRangeValue(array $options = []): int|float|null
    {
        return null;
    }

    public function configureOptions(OptionsResolver $optionsResolver): void
    {
        $optionsResolver->setRequired('handler');
        $optionsResolver->setAllowedValues('handler', ['php', 'file_server']);
    }
}
