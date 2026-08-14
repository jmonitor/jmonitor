<?php

declare(strict_types=1);

namespace App\Twig\Components\Dashboard;

use App\Metrics\CollectorContext;
use App\Metrics\LastPush\LastPushBag;
use App\Metrics\LastPush\LastPushStatus;
use App\Version\AdvertisedVersion;
use App\Version\Package;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;
use Symfony\UX\TwigComponent\Attribute\ExposeInTemplate;

#[AsTwigComponent]
readonly class Status
{
    public function __construct(
        private CollectorContext $collectorContext,
    ) {}

    #[ExposeInTemplate]
    public function getLastPush(): ?LastPushBag
    {
        return $this->collectorContext->getLastPushBag();
    }

    #[ExposeInTemplate]
    public function getLastPushStatus(): LastPushStatus
    {
        return $this->collectorContext->getLastPushStatus();
    }

    #[ExposeInTemplate]
    public function getCollectorVersion(): AdvertisedVersion
    {
        return $this->collectorContext->getAdvertisedVersion(Package::COLLECTOR);
    }

    #[ExposeInTemplate]
    public function getBundleVersion(): AdvertisedVersion
    {
        return $this->collectorContext->getAdvertisedVersion(Package::BUNDLE);
    }
}
