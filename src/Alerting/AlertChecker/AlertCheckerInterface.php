<?php

namespace App\Alerting\AlertChecker;

use App\Alerting\Dto\SpottedAlert;
use App\Entity\Alert;
use App\Metrics\Dto\MetricBagDto;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('app.alert.checker')]
interface AlertCheckerInterface
{
    public function support(Alert $alert, MetricBagDto $metricBag): bool;
    public function check(Alert $alert, MetricBagDto $metricBag): ?SpottedAlert;
}
