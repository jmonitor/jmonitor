<?php

declare(strict_types=1);

namespace App\Metrics\Metric\Mysql;

use App\Metrics\BagAwareTrait;
use App\Metrics\Metric;
use App\Metrics\Metric\GaugeMetricInterface;
use App\Metrics\Renderer\Dto\GaugeDto;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

#[AsTaggedItem(Metric::MysqlConnectionsUsage->value)]
class MysqlConnexionUsage implements GaugeMetricInterface
{
    use BagAwareTrait;

    public function getMetric(): Metric
    {
        return Metric::MysqlConnectionsUsage;
    }

    public function configureGauge(GaugeDto $gauge, array $options = []): void
    {
        $statusBag = $this->getMysqlStatusBag();
        $variablesBag = $this->getMysqlVariablesBag();

        $gauge
            ->setValueAvailable($statusBag && $variablesBag)
            ->setValue($this->getGaugeValue(), 0);
    }

    public function getTypicalRangeValue(array $options = []): int|float|null
    {
        return $this->getGaugeValue();
    }

    private function getGaugeValue(): ?float
    {
        $statusBag = $this->getMysqlStatusBag();
        $variablesBag = $this->getMysqlVariablesBag();

        if (!$statusBag || !$variablesBag) {
            return null;
        }

        if ($statusBag->threadsConnected === null || $variablesBag->maxConnections <= 0) {
            return null;
        }

        return round($statusBag->threadsConnected / $variablesBag->maxConnections, 2) * 100;
    }
}
