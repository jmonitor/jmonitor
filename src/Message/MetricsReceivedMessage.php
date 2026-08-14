<?php

declare(strict_types=1);

namespace App\Message;

readonly class MetricsReceivedMessage
{
    private \DateTimeImmutable $receivedAt;

    /**
     * $bundleVersion defaults to null so messages queued by a previous deployment still
     * unserialize while they drain.
     */
    public function __construct(private int $projectId, private array $metrics, private string $jmonitorVersion, private ?string $bundleVersion = null)
    {
        $this->receivedAt = new \DateTimeImmutable();
    }

    public function getProjectId(): int
    {
        return $this->projectId;
    }

    public function getMetrics(): array
    {
        return $this->metrics;
    }

    public function getReceivedAt(): \DateTimeImmutable
    {
        return $this->receivedAt;
    }

    public function getJmonitorVersion(): string
    {
        return $this->jmonitorVersion;
    }

    public function getBundleVersion(): ?string
    {
        return $this->bundleVersion;
    }
}
