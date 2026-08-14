<?php

declare(strict_types=1);

namespace App\Message;

readonly class MetricsReceivedMessage
{
    private \DateTimeImmutable $receivedAt;

    public function __construct(private int $projectId, private array $metrics, private string $jmonitorVersion, private ?string $bundleVersion = null)
    {
        $this->receivedAt = new \DateTimeImmutable();
    }

    /**
     * Messages a deployment leaves queued are unserialized without their constructor,
     * so a property added since carries no default and stays uninitialized.
     *
     * @param array<string, mixed> $data
     */
    public function __unserialize(array $data): void
    {
        $property = static fn(string $name): string => sprintf("\0%s\0%s", self::class, $name);

        $this->projectId = $data[$property('projectId')];
        $this->metrics = $data[$property('metrics')];
        $this->jmonitorVersion = $data[$property('jmonitorVersion')];
        $this->bundleVersion = $data[$property('bundleVersion')] ?? null;
        $this->receivedAt = $data[$property('receivedAt')];
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
