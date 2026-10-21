<?php

declare(strict_types=1);

namespace App\Metrics\Renderer;

use App\Bridge\InfluxDb\InfluxDb;
use App\Chart\TimeRange;
use App\Metrics\Model\Influx\QueryBuilder;
use App\Metrics\Renderer\Dto\TimeSerieDto;
use App\Metrics\Renderer\Error\RenderingException;
use App\Project\ProjectContext;
use App\Range\RangeContext;
use App\Security\Voter\Right\Right;
use InfluxDB2\FluxTable;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Contracts\Service\ResetInterface;

/**
 * Fetches data from InfluxDB according to a TimeSerieDto configuration.
 */
class InfluxChartDataProvider implements ResetInterface
{
    private ?int $errorTime = null;

    public function __construct(
        private readonly ProjectContext $projectContext,
        private readonly RangeContext $rangeContext,
        private readonly InfluxDb $influxDb,
        private readonly LoggerInterface $logger,
        private readonly Security $security,
    ) {}

    /**
     * Fetches the InfluxDB tables and transforms them into ChartJS datasets.
     *
     * @return array<int, array{label: string, data: array<string, mixed>}>
     */
    public function getDatasets(TimeSerieDto $dto, ?TimeRange $range): array
    {
        $tables = $this->getTables($dto, $range);

        $datasets = [];

        foreach ($tables as $table) {
            $field = $table->records[0]->getField();

            $dataset = [
                'label' => $dto->getFieldLabel($field) ?? $field,
                'data' => [],
                // add a key of our own, more convenient later on
                'field_name' => $field,
            ];

            foreach ($table->records as $record) {
                $dataset['data'][$record->getTime()] = $record->getValue();
            }

            $datasets[] = $dataset;
        }

        return $datasets;
    }

    /**
     * @return FluxTable[]
     */
    private function getTables(TimeSerieDto $dto, ?TimeRange $range): array
    {
        $range ??= $this->rangeContext->getRangeDto()->range;

        $queryBuilder = $this
            ->createQueryBuilder($dto->measurement, $range)
            ->fields(array_keys($dto->fields));

        if ($dto->queryBuilder) {
            ($dto->queryBuilder)($queryBuilder, $range);
        }

        $queryBuilder->aggregateWindow($range);

        return $this->query($queryBuilder);
    }

    /**
     * How much a cumulative counter grew over the range.
     * Null when the project keeps no history, or with fewer than two points over the range.
     */
    public function getTotalIncrease(string $measurement, string $field, TimeRange $range): ?int
    {
        $project = $this->projectContext->getCurrentProject();

        if (!$project?->getBucketId() || !$this->security->isGranted(Right::TIME_SERIES_CHART->value, $project)) {
            return null;
        }

        try {
            $tables = $this->query($this->createQueryBuilder($measurement, $range)->field($field)->totalIncrease());
        } catch (RenderingException) {
            return null;
        }

        $value = ($tables[0]->records[0] ?? null)?->getValue();

        return $value !== null ? (int) $value : null;
    }

    /**
     * @return FluxTable[]
     */
    private function query(QueryBuilder $queryBuilder): array
    {
        if ($this->errorTime) {
            throw new RenderingException();
        }

        try {
            return $this->influxDb->queryApi->query($queryBuilder->getQuery()) ?? [];
        } catch (\Throwable $e) {
            $this->logger->error('InfluxDB query error (caught)', [
                'exception' => $e,
                'message' => $e->getMessage(),
                'query' => $queryBuilder->getQuery(),
                'project' => [
                    'id' => $this->projectContext->getCurrentProject()->getId(),
                    'name' => $this->projectContext->getCurrentProject()->getName(),
                ],
            ]);

            $this->errorTime = time();

            throw new RenderingException();
        }
    }

    private function createQueryBuilder(string $measurement, TimeRange $range): QueryBuilder
    {
        return new QueryBuilder($this->projectContext->getCurrentProject()->getBucketName())
            ->range($range)
            ->measurement($measurement);
    }

    public function reset(): void
    {
        $this->errorTime = null;
    }
}
