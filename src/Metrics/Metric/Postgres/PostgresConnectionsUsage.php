<?php

declare(strict_types=1);

namespace App\Metrics\Metric\Postgres;

use App\Metrics\BagAwareTrait;
use App\Metrics\Metric;
use App\Metrics\Metric\GaugeMetricInterface;
use App\Metrics\Renderer\Dto\GaugeDto;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

#[AsTaggedItem(Metric::PostgresConnectionsUsage->value)]
class PostgresConnectionsUsage implements GaugeMetricInterface
{
    use BagAwareTrait;

    public function getMetric(): Metric
    {
        return Metric::PostgresConnectionsUsage;
    }

    public function configureGauge(GaugeDto $gauge, array $options = []): void
    {
        $gauge
            ->setValueAvailable($this->getValue() !== null)
            ->setValue($this->getValue(), 0)
            ->setContext([
                'used' => $this->getPostgresActivityBag()?->numbackends,
                'max' => $this->getPostgresSettingsBag()?->maxConnections,
            ]);
    }

    public function getTypicalRangeValue(array $options = []): int|float|null
    {
        return $this->getValue();
    }

    private function getValue(): ?float
    {
        $used = $this->getPostgresActivityBag()?->numbackends;
        $max = $this->getPostgresSettingsBag()?->maxConnections;

        if ($used === null || $max === null || $max <= 0) {
            return null;
        }

        return round($used / $max, 2) * 100;
    }
}
