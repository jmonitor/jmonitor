<?php

declare(strict_types=1);

namespace App\Event;

use App\Entity\Project;
use App\Metrics\Dto\MetricBagDto;
use Symfony\Contracts\EventDispatcher\Event;

class PostConsumeEvent extends Event
{
    /** @var MetricBagDto[]  */
    public private(set) array $metricBags;
    public private(set) Project $project;
    public private(set) \DateTimeImmutable $receivedAt;
    public private(set) string $jmonitorVersion;

    /**
     * @params MetricBagDto[] $metricBags
     */
    public function __construct(Project $project, array $metricBags, \DateTimeImmutable $receivedAt, string $jmonitorVersion)
    {
        $this->metricBags = $metricBags;
        $this->project = $project;
        $this->receivedAt = $receivedAt;
        $this->jmonitorVersion = $jmonitorVersion;
    }

    /**
     * Returns the names of the components that were updated.
     * @return string[]
     */
    public function getComponents(): array
    {
        return array_values(array_unique(array_map(
            fn(MetricBagDto $metricBag): string => $metricBag->getConsumer()->getComponent()->value,
            $this->metricBags,
        )));
    }
}
