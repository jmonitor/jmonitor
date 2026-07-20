<?php

declare(strict_types=1);

namespace App\Metrics\Consumer\Apache;

use App\Metrics\Consumer\Consumer;
use App\Metrics\Consumer\ConsumerInterface;
use App\Metrics\DeltaCalculator;
use App\Metrics\Dto\Bag\Apache\ApacheBag;
use App\Metrics\Dto\MetricBagDto;
use InfluxDB2\Point;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\Collection;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\Type;

/**
 * https://statuslist.app/apache/status-scoreboard-explained/
 */
#[AsTaggedItem(Consumer::APACHE->value)]
class ApacheConsumer implements ConsumerInterface
{
    private DeltaCalculator $deltaCalculator;

    public function __construct(DeltaCalculator $deltaCalculator)
    {
        $this->deltaCalculator = $deltaCalculator;
    }

    public function normalizeBag(MetricBagDto $bag): MetricBagDto
    {
        return $bag;
    }

    /**
     * @param ApacheBag $bag
     */
    public function getMetricsToCache(MetricBagDto $bag): array
    {
        // add some per-second metrics computed by ourselves,
        // because the ones provided by Apache are averages since its last restart, which makes them useless
        $deltas = $this->deltaCalculator->getDelta($bag);

        $data = $bag->all();
        $data['real_requests_per_second'] = $deltas?->getPerSec('total_accesses');
        $data['real_bytes_per_second'] = $deltas?->getPerSec('total_bytes');

        return $data;
    }

    /**
     * @param ApacheBag $bag
     */
    public function getInfluxPoints(MetricBagDto $bag): array
    {
        $datas = array_filter([
            'load1' => $bag->load1,
            'load5' => $bag->load5,
            'load15' => $bag->load15,
            'workers_busy' => $bag->workers->busy,
            'workers_total' => $bag->workers->total,
            'total_accesses' => $bag->totalAccesses,
            'total_bytes' => $bag->totalBytes,
        ], fn($v): bool => $v !== null);

        if (!$datas) {
            return [];
        }

        $point = new Point('apache');

        foreach ($datas as $k => $v) {
            $point->addField($k, $v);
        }

        return [$point];
    }

    /**
     * @return Constraint|Constraint[]
     */
    public function getConstraints(int $version): Constraint|array
    {
        return new Collection(
            fields: [
                'server_version' => [new Type('string'), new Length(max: 100)],
                'server_mpm' => [new Type('string'), new Length(max: 32)],
                'uptime' => [new Type('int'), new GreaterThanOrEqual(0)],
                'load1' => new Type(type: ['float', 'int']),
                'load5' => new Type(type: ['float', 'int']),
                'load15' => new Type(type: ['float', 'int']),
                'total_accesses' => [new Type('int'), new GreaterThanOrEqual(0)],
                'total_bytes' => [new Type('int'), new GreaterThanOrEqual(0)],
                'requests_per_second' => [new Type('int'), new GreaterThanOrEqual(0)],
                'bytes_per_second' => [new Type('int'), new GreaterThanOrEqual(0)],
                'bytes_per_request' => [new Type('int'), new GreaterThanOrEqual(0)],
                'duration_per_request' => [new Type('int'), new GreaterThanOrEqual(0)],
                'workers' => new Collection(
                    fields: [
                        'busy' => [new Type('int'), new GreaterThanOrEqual(0)],
                        'idle' => [new Type('int'), new GreaterThanOrEqual(0)],
                    ],
                ),
                'scoreboard' => new Collection(
                    fields: [
                        '_' => [new Type('int'), new GreaterThanOrEqual(0)],
                        'W' => [new Type('int'), new GreaterThanOrEqual(0)],
                        'S' => [new Type('int'), new GreaterThanOrEqual(0)],
                        'R' => [new Type('int'), new GreaterThanOrEqual(0)],
                        'K' => [new Type('int'), new GreaterThanOrEqual(0)],
                        'D' => [new Type('int'), new GreaterThanOrEqual(0)],
                        'C' => [new Type('int'), new GreaterThanOrEqual(0)],
                        'L' => [new Type('int'), new GreaterThanOrEqual(0)],
                        'G' => [new Type('int'), new GreaterThanOrEqual(0)],
                        'I' => [new Type('int'), new GreaterThanOrEqual(0)],
                        '.' => [new Type('int'), new GreaterThanOrEqual(0)],
                    ],
                    allowMissingFields: true,
                ),
                // list of enabled modules
                'modules' => [
                    new Type('array'),
                    new Assert\All([new Type('string'), new Length(max: 128)]),
                ],
            ],
            allowMissingFields: true,
        );
    }
}
