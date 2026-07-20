<?php

declare(strict_types=1);

namespace App\Metrics\Dto;

use App\Entity\Project;
use App\Metrics\Consumer\Consumer;

abstract class MetricBagDto extends Bag
{
    private readonly Project $project;
    private readonly Consumer $consumer;
    private readonly int $version;
    private readonly \DateTimeImmutable $receivedAt;
    private readonly bool $threw;

    /**
     * @param mixed[] $metrics
     */
    public static function create(Project $project, Consumer $consumer, int $version, array $metrics, \DateTimeImmutable $receivedAt, bool $threw): self
    {
        $class = $consumer->metricBagClass();

        return new $class($project, $consumer, $version, $metrics, $receivedAt, $threw);
    }

    /**
     * @param mixed[] $metrics
     */
    private function __construct(Project $project, Consumer $consumer, int $version, array $metrics, \DateTimeImmutable $receivedAt, bool $threw)
    {
        parent::__construct($metrics);

        $this->receivedAt = $receivedAt;
        $this->version = $version;
        $this->consumer = $consumer;
        $this->project = $project;
        $this->threw = $threw;
    }

    public function getConsumer(): Consumer
    {
        return $this->consumer;
    }

    public function getVersion(): int
    {
        return $this->version;
    }

    public function getReceivedAt(): \DateTimeImmutable
    {
        return $this->receivedAt;
    }

    public function getProject(): Project
    {
        return $this->project;
    }

    public function hasThrew(): bool
    {
        return $this->threw;
    }

    /**
     * @param mixed[] $metrics
     */
    public function withMetrics(array $metrics): self
    {
        return $this->withParameters($metrics);
    }
}
