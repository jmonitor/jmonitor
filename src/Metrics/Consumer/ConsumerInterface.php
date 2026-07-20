<?php

declare(strict_types=1);

namespace App\Metrics\Consumer;

use App\Metrics\Dto\MetricBagDto;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\Validator\Constraint;

#[AutoconfigureTag('app.consumer')]
interface ConsumerInterface
{
    public function normalizeBag(MetricBagDto $bag): MetricBagDto;

    public function getMetricsToCache(MetricBagDto $bag): array;
    public function getInfluxPoints(MetricBagDto $bag): array;

    /**
     * Returns the validation constraints for the metrics.
     */
    public function getConstraints(int $version): Constraint|array;
}
