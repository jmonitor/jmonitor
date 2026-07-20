<?php

declare(strict_types=1);

namespace App\Metrics;

use App\Metrics\Metric\MetricInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireLocator;

readonly class MetricLocator
{
    public function __construct(
        #[AutowireLocator('app.metric')]
        private ContainerInterface $metrics,
    ) {}

    public function get(string|Metric $metric): MetricInterface
    {
        return $metric instanceof Metric ? $this->metrics->get($metric->value) : $this->metrics->get($metric);
    }

    public function has(string|Metric $metric): bool
    {
        return $metric instanceof Metric ? $this->metrics->has($metric->value) : $this->metrics->has($metric);
    }
}
