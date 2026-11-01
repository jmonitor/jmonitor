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
    private readonly bool $skipped;

    /**
     * @param mixed[] $metrics
     */
    public static function create(Project $project, Consumer $consumer, int $version, array $metrics, \DateTimeImmutable $receivedAt, bool $threw, bool $skipped = false): self
    {
        $class = $consumer->metricBagClass();

        return new $class($project, $consumer, $version, $metrics, $receivedAt, $threw, $skipped);
    }

    /**
     * @param mixed[] $metrics
     */
    private function __construct(Project $project, Consumer $consumer, int $version, array $metrics, \DateTimeImmutable $receivedAt, bool $threw, bool $skipped)
    {
        parent::__construct($metrics);

        $this->receivedAt = $receivedAt;
        $this->version = $version;
        $this->consumer = $consumer;
        $this->project = $project;
        $this->threw = $threw;
        $this->skipped = $skipped;
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
     * The agent could not boot this collector and sent no metrics.
     */
    public function hasSkipped(): bool
    {
        return $this->skipped;
    }

    /**
     * @param mixed[] $metrics
     */
    public function withMetrics(array $metrics): self
    {
        return $this->withParameters($metrics);
    }
}
