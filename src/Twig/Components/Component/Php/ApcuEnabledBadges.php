<?php

declare(strict_types=1);

namespace App\Twig\Components\Component\Php;

use App\Metrics\Consumer\Consumer;
use App\Metrics\Dto\Bag\Php\Apcu\ApcuBag;
use App\Metrics\Dto\Bag\Php\PhpBag;
use App\Metrics\MetricsBagProvider;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;
use Symfony\UX\TwigComponent\Attribute\ExposeInTemplate;

#[AsTwigComponent]
class ApcuEnabledBadges
{
    private readonly MetricsBagProvider $metricsBagProvider;

    public function __construct(MetricsBagProvider $metricsBagProvider)
    {
        $this->metricsBagProvider = $metricsBagProvider;
    }

    #[ExposeInTemplate]
    public function getEnabled(): ?bool
    {
        return $this->getApcuBag()?->config->enabled;
    }

    #[ExposeInTemplate]
    public function getEnabledCli(): ?bool
    {
        return $this->getApcuBag()?->config->enabledCli;
    }

    #[ExposeInTemplate]
    public function getApcuBag(): ?ApcuBag
    {
        return $this->metricsBagProvider->getLastBag(Consumer::PHP, PhpBag::class)?->apcu;
    }
}
