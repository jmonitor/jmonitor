<?php

declare(strict_types=1);

namespace App\Metrics\Metric\Cloudflare;

use App\Metrics\Dto\Bag\Cloudflare\ZoneBag;
use App\Metrics\Metric;
use App\Metrics\Metric\BasicMetricInterface;
use App\Metrics\Renderer\Dto\BasicDto;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

#[AsTaggedItem(Metric::CloudflareCertificate->value)]
class CloudflareCertificate implements BasicMetricInterface
{
    use CloudflareBlockTrait;

    private const int WARNING_DAYS = 14;

    public function getMetric(): Metric
    {
        return Metric::CloudflareCertificate;
    }

    public function configureBasicDto(BasicDto $dto, array $options = []): void
    {
        $zone = $this->block($dto, 'zone');

        if (!$zone instanceof ZoneBag) {
            return;
        }

        $certificate = $this->subBlock($dto, $zone->certificate);

        if ($certificate === null) {
            return;
        }

        $daysLeft = $certificate->daysLeft(new \DateTimeImmutable());

        $dto
            ->setValue($certificate)
            ->setContext([
                'daysLeft' => $daysLeft,
                'style' => match (true) {
                    $certificate->status !== 'active', $daysLeft !== null && $daysLeft < 0 => 'danger',
                    $daysLeft !== null && $daysLeft <= self::WARNING_DAYS => 'warning',
                    default => 'success',
                },
            ])
        ;
    }
}
