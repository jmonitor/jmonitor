<?php

declare(strict_types=1);

namespace App\Metrics\Model\Influx;

use App\Bridge\InfluxDb\InfluxDb;
use App\Chart\TimeRange;
use App\Project\ProjectContext;
use App\Security\Voter\Right\Right;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * How much a cumulative counter grew over a range, a reset (process restart) counting from zero again.
 */
readonly class CounterIncrease
{
    public function __construct(
        private ProjectContext $projectContext,
        private InfluxDb $influxDb,
        private Security $security,
        private LoggerInterface $logger,
    ) {}

    /**
     * Null when the history is not kept for the project, or holds fewer than two points over the range.
     */
    public function get(string $measurement, string $field, TimeRange $range): ?int
    {
        $project = $this->projectContext->getCurrentProject();

        if (!$project->getBucketId() || !$this->security->isGranted(Right::TIME_SERIES_CHART->value, $project)) {
            return null;
        }

        $query = new QueryBuilder($project->getBucketName())
            ->range($range)
            ->measurement($measurement)
            ->field($field)
            ->getQuery() . ' |> difference(nonNegative: true) |> sum()';

        try {
            $tables = $this->influxDb->queryApi->query($query) ?? [];
        } catch (\Throwable $e) {
            $this->logger->error('InfluxDB query error (caught)', [
                'exception' => $e,
                'query' => $query,
                'project' => $project->getId(),
            ]);

            return null;
        }

        $value = ($tables[0]->records[0] ?? null)?->getValue();

        return $value !== null ? (int) $value : null;
    }
}
