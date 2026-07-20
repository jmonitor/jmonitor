<?php

declare(strict_types=1);

namespace App\Metrics\Consumer\Mysql;

use App\Metrics\Consumer\Consumer;
use App\Metrics\Consumer\ConsumerInterface;
use App\Metrics\DeltaCalculator;
use App\Metrics\Dto\Bag\Mysql\MysqlStatusBag;
use App\Metrics\Dto\MetricBagDto;
use InfluxDB2\Point;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\Collection;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;
use Symfony\Component\Validator\Constraints\Type;

#[AsTaggedItem(Consumer::MYSQL_STATUS->value)]
class StatusConsumer implements ConsumerInterface
{
    private DeltaCalculator $deltaCalculator;

    public function __construct(DeltaCalculator $deltaCalculator)
    {
        $this->deltaCalculator = $deltaCalculator;
    }

    public function normalizeBag(MetricBagDto $bag): MetricBagDto
    {
        $delta = $this->deltaCalculator->getDelta($bag);

        if (!$delta) {
            return $bag;
        }

        $data = $bag->all();

        $data['questions_per_second'] = $delta->getPerSec('Questions');
        $data['innodb_data_reads_per_second'] = $delta->getPerSec('Innodb_data_reads');
        $data['innodb_data_writes_per_second'] = $delta->getPerSec('Innodb_data_writes');
        $data['table_locks_waited_per_second'] = $delta->getPerSec('Table_locks_waited');

        // lock wait ratio
        $data['table_locks_waited_delta'] = $delta->getValue('Table_locks_waited');
        $data['table_locks_immediate_delta'] = $delta->getValue('Table_locks_immediate');

        if ($data['table_locks_waited_delta'] !== null && $data['table_locks_immediate_delta'] !== null) {
            $total = $data['table_locks_waited_delta'] + $data['table_locks_immediate_delta'];

            $data['table_locks_waited_percent'] = $total > 0 ? round($data['table_locks_waited_delta'] / $total * 100, 2) : null;
        }

        return $bag->withParameters($data);
    }

    /**
     * @param MysqlStatusBag $bag
     */
    public function getMetricsToCache(MetricBagDto $bag): array
    {
        $data = $bag->all();

        $data['questions_per_second'] = $this->deltaCalculator->getDelta($bag)?->getPerSec('Questions');

        return $data;
    }

    /**
     * @param MysqlStatusBag $bag
     */
    public function getInfluxPoints(MetricBagDto $bag): array
    {
        $fields = [
            'threads_connected' => $bag->threadsConnected,
            'threads_running' => $bag->threadsRunning,
            'questions' => $bag->questions,
            'com_select' => $bag->comSelect,
            'com_insert' => $bag->comInsert,
            'com_update' => $bag->comUpdate,
            'com_delete' => $bag->comDelete,
            'slow_queries' => $bag->slowQueries,
            'innodb_data_reads' => $bag->innodbDataReads,
            'innodb_data_writes' => $bag->innodbDataWrites,
            'table_locks_waited' => $bag->tableLocksWaited,
        ];

        $fields = array_filter($fields, fn($v): bool => $v !== null);

        if (!$fields) {
            return [];
        }

        $point = new Point('mysql_status');

        foreach ($fields as $key => $value) {
            $point->addField($key, $value);
        }

        return [$point];
    }

    public function getConstraints(int $version): Constraint|array
    {
        return new Collection(
            fields: [
                'Aborted_clients' => [new Type('numeric'), new GreaterThanOrEqual(0)],
                'Aborted_connects' => [new Type('numeric'), new GreaterThanOrEqual(0)],
                'Com_delete' => [new Type('numeric'), new GreaterThanOrEqual(0)],
                'Com_insert' => [new Type('numeric'), new GreaterThanOrEqual(0)],
                'Com_select' => [new Type('numeric'), new GreaterThanOrEqual(0)],
                'Com_update' => [new Type('numeric'), new GreaterThanOrEqual(0)],
                'Connections' => [new Type('numeric'), new GreaterThanOrEqual(0)],
                'Created_tmp_disk_tables' => [new Type('numeric'), new GreaterThanOrEqual(0)],
                'Created_tmp_tables' => [new Type('numeric'), new GreaterThanOrEqual(0)],
                'Innodb_buffer_pool_bytes_data' => [new Type('numeric'), new GreaterThanOrEqual(0)],
                'Innodb_buffer_pool_bytes_free' => [new Type('numeric'), new GreaterThanOrEqual(0)],
                'Innodb_buffer_pool_read_requests' => [new Type('numeric'), new GreaterThanOrEqual(0)],
                'Innodb_buffer_pool_reads' => [new Type('numeric'), new GreaterThanOrEqual(0)],
                'Innodb_buffer_pool_pages_total' => [new Type('numeric'), new GreaterThanOrEqual(0)],
                'Innodb_buffer_pool_pages_free' => [new Type('numeric'), new GreaterThanOrEqual(0)],
                'Innodb_page_size' => [new Type('numeric'), new GreaterThanOrEqual(0)],
                'Innodb_data_read' => [new Type('numeric'), new GreaterThanOrEqual(0)],
                'Innodb_data_reads' => [new Type('numeric'), new GreaterThanOrEqual(0)],
                'Innodb_data_writes' => [new Type('numeric'), new GreaterThanOrEqual(0)],
                'Innodb_data_written' => [new Type('numeric'), new GreaterThanOrEqual(0)],
                'Max_used_connections' => [new Type('numeric'), new GreaterThanOrEqual(0)],
                'Questions' => [new Type('numeric'), new GreaterThanOrEqual(0)],
                'Slow_queries' => [new Type('numeric'), new GreaterThanOrEqual(0)],
                'Table_locks_immediate' => [new Type('numeric'), new GreaterThanOrEqual(0)],
                'Table_locks_waited' => [new Type('numeric'), new GreaterThanOrEqual(0)],
                'Threads_connected' => [new Type('numeric'), new GreaterThanOrEqual(0)],
                'Threads_created' => [new Type('numeric'), new GreaterThanOrEqual(0)],
                'Threads_running' => [new Type('numeric'), new GreaterThanOrEqual(0)],
                'Uptime' => [new Type('numeric'), new GreaterThanOrEqual(0)],
            ],
            allowMissingFields: true,
        );
    }
}
