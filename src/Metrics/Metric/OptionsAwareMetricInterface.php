<?php

declare(strict_types=1);

namespace App\Metrics\Metric;

use Symfony\Component\OptionsResolver\OptionsResolver;

interface OptionsAwareMetricInterface extends MetricInterface
{
    public function configureOptions(OptionsResolver $optionsResolver): void;
}
