<?php

declare(strict_types=1);

namespace App\Metrics\Consumer\FrankenPhp;

use App\Metrics\Consumer\Consumer;
use App\Metrics\Consumer\ConsumerInterface;
use App\Metrics\DeltaCalculator;
use App\Plan\PlanResolver;
use App\Metrics\Dto\Bag\FrankenPhp\FrankenPhpBag;
use App\Metrics\Dto\MetricBagDto;
use InfluxDB2\Point;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\Collection;

#[AsTaggedItem(Consumer::FRANKENPHP->value)]
readonly class FrankenPhpConsumer implements ConsumerInterface
{
    public function __construct(
        private DeltaCalculator $deltaCalculator,
        private PlanResolver $planResolver,
    ) {}

    public function normalizeBag(MetricBagDto $bag): MetricBagDto
    {
        $data = $bag->all();
        $delta = $this->deltaCalculator->getDelta($bag);

        if ($delta) {
            foreach ($data['workers'] ?? [] as $i => $worker) {

                // exec time
                $workerRequestTimeDelta = $delta->getValue('workers.' . $i . '.worker_request_time');
                $workerRequestCountDelta = $delta->getValue('workers.' . $i . '.worker_request_count');

                if ($workerRequestTimeDelta !== null && $workerRequestCountDelta > 0) {
                    $data['workers'][$i]['real_worker_request_time_avg'] = $workerRequestTimeDelta / $workerRequestCountDelta;
                }

                // req per sec
                $data['workers'][$i]['worker_request_per_sec'] = $delta->getPerSec('workers.' . $i . '.worker_request_count');
            }

            return $bag->withMetrics($data);
        }

        return $bag;
    }

    public function getMetricsToCache(MetricBagDto $bag): array
    {
        return $bag->all();
    }

    /**
     * @param FrankenPhpBag $bag
     */
    public function getInfluxPoints(MetricBagDto $bag): array
    {
        $points = array_merge([$this->getFrankenPoint($bag)], $this->getWorkersPoints($bag));

        return array_filter($points);
    }

    /**
     * @return Constraint|Constraint[]
     */
    public function getConstraints(int $version): Constraint|array
    {
        return new Collection(
            fields: [
                'version' => [new Assert\Type('string'), new Assert\Length(max: 200)],
                'mode' => [new Assert\Type('string'), new Assert\Choice(choices: ['worker', 'classic'])],
                'busy_threads' => [new Assert\Type('int'), new Assert\GreaterThanOrEqual(0)],
                'total_threads' => [new Assert\Type('int'), new Assert\GreaterThanOrEqual(0)],
                'queue_depth' => [new Assert\Type('int'), new Assert\GreaterThanOrEqual(0)],
                'workers' => new Assert\All([
                    new Collection(
                        fields: [
                            'name' => new Assert\Type('string'),
                            'total_workers' => [new Assert\Type('int'), new Assert\GreaterThanOrEqual(0)],
                            'busy_workers' => [new Assert\Type('int'), new Assert\GreaterThanOrEqual(0)],
                            'worker_request_time' => [new Assert\Type(['float', 'int']), new Assert\GreaterThanOrEqual(0)],
                            'worker_request_count' => [new Assert\Type('int'), new Assert\GreaterThanOrEqual(0)],
                            'ready_workers' => [new Assert\Type('int'), new Assert\GreaterThanOrEqual(0)],
                            'worker_restarts' => [new Assert\Type('int'), new Assert\GreaterThanOrEqual(0)],
                            'worker_crashes' => [new Assert\Type('int'), new Assert\GreaterThanOrEqual(0)],
                            'worker_queue_depth' => [new Assert\Type('int'), new Assert\GreaterThanOrEqual(0)],
                        ],
                        allowMissingFields: true,
                    ),
                ]),
            ],
            allowMissingFields: true,
        );
    }

    private function getFrankenPoint(FrankenPhpBag $bag): ?Point
    {
        $datas = array_filter([
            'busy_threads' => $bag->busyThreads,
            'busy_threads_percent' => $bag->busyThreadsPercent,
        ], fn($v): bool => $v !== null);

        if (!$datas) {
            return null;
        }

        $point = new Point('frankenphp');

        foreach ($datas as $k => $v) {
            $point->addField($k, $v);
        }

        return $point;
    }

    private function getWorkersPoints(FrankenPhpBag $bag): array
    {
        $points = [];

        $workers = array_slice($bag->workers, 0, $this->planResolver->resolve($bag->getProject())->nbFrankenPhpWorkers());

        foreach ($workers as $i => $worker) {
            $datas = array_filter([
                'worker_request_count' => $worker->workerRequestCount,
                'busy_workers' => $worker->busyWorker,
                'busy_workers_percent' => $worker->busyWorkerPercent,
                'real_request_time_avg_ms' => $worker->realWorkerRequestTimeAvgMs,
            ], fn($v): bool => $v !== null);

            if (!$datas) {
                continue;
            }

            $points[] = new Point('frankenphp_worker', tags: ['worker' => $i], fields: $datas);
        }

        return $points;
    }
}
