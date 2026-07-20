<?php

declare(strict_types=1);

namespace App\Twig\Components\Component\Php;

use App\Metrics\Consumer\Consumer;
use App\Metrics\Dto\Bag\Php\Opcache\OpcacheBag;
use App\Metrics\Dto\Bag\Php\PhpBag;
use App\Metrics\MetricsBagProvider;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;
use Symfony\UX\TwigComponent\Attribute\ExposeInTemplate;

#[AsTwigComponent]
class OpcacheEnabledBadges
{
    private readonly MetricsBagProvider $metricsBagProvider;

    public function __construct(MetricsBagProvider $metricsBagProvider)
    {
        $this->metricsBagProvider = $metricsBagProvider;
    }

    #[ExposeInTemplate]
    public function getEnabled(): ?bool
    {
        return $this->getOpcacheBag()?->config->directives->enabled;
    }

    #[ExposeInTemplate]
    public function getEnabledCli(): ?bool
    {
        return $this->getOpcacheBag()?->config->directives->enabledCli;
    }

    #[ExposeInTemplate]
    public function getOpcacheBag(): ?OpcacheBag
    {
        return $this->metricsBagProvider->getLastBag(Consumer::PHP, PhpBag::class)?->opcache;
    }
}
