<?php

declare(strict_types=1);

namespace App\Alerting\AlertChecker;

use App\Alerting\AlertMetric;
use App\Alerting\Config\SymfonyTransportMessagesConfig;
use App\Alerting\Dto\SpottedAlert;
use App\Entity\Alert;
use App\Entity\Enums\AlertType;
use App\Metrics\Dto\Bag\Symfony\SymfonyBag;
use App\Metrics\Dto\MetricBagDto;

class CustomTypeChecker implements AlertCheckerInterface
{
    public function support(Alert $alert, MetricBagDto $metricBag): bool
    {
        return $alert->getAlertMetric()->getType() === AlertType::Custom;
    }

    public function check(Alert $alert, MetricBagDto $metricBag): ?SpottedAlert
    {
        return match ($alert->getAlertMetric()) {
            AlertMetric::SymfonyTransportMessages => $this->checkSymfonyTransportMessages($alert, $metricBag),
            AlertMetric::SymfonyOutdatedFlexRecipes => $this->checkSymfonyOutdatedFlexRecipes($alert, $metricBag),
            default => null,
        };
    }

    private function checkSymfonyOutdatedFlexRecipes(Alert $alert, MetricBagDto $bag): ?SpottedAlert
    {
        assert($bag instanceof SymfonyBag);

        $flex = $bag->components->flexRecipes;

        // missing data (old collector, composer unavailable, etc.) -> no false alert
        if ($flex->upToDate === null) {
            return null;
        }

        if ($flex->upToDate === true) {
            return null;
        }

        $outdated = $flex->outdatedRecipes;

        return new SpottedAlert($alert, count($outdated), $outdated);
    }

    private function checkSymfonyTransportMessages(Alert $alert, MetricBagDto $bag): ?SpottedAlert
    {
        $config = $alert->getConfig();

        assert($config instanceof SymfonyTransportMessagesConfig);
        assert($bag instanceof SymfonyBag);

        $nb = $bag->components->messenger->getTransport($config->transportName)['count'] ?? null;

        if ($nb === null) {
            return null;
        }

        if ($nb < $config->threshold) {
            return null;
        }

        return new SpottedAlert($alert, $nb);
    }
}
