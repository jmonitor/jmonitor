<?php

declare(strict_types=1);

namespace App\Message;

readonly class CheckAlertMessage
{
    public private(set) int $projectId;

    /**
     * @var array<array{consumer: string, metrics: array, receivedAt: \DateTimeImmutable, version: int, threw: bool}>
     */
    public private(set) array $bags;

    /**
     * @param array<array{consumer: string, metrics: array, receivedAt: \DateTimeImmutable, version: int, threw: bool}> $bags
     */
    public function __construct(int $projectId, array $bags)
    {
        $this->projectId = $projectId;
        $this->bags = $bags;
    }
}
