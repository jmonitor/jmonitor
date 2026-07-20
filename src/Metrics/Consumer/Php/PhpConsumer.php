<?php

declare(strict_types=1);

namespace App\Metrics\Consumer\Php;

use App\Metrics\Consumer\Consumer;
use App\Metrics\Consumer\ConsumerInterface;
use App\Metrics\DeltaCalculator;
use App\Metrics\Dto\Bag\Php\PhpBag;
use App\Metrics\Dto\MetricBagDto;
use InfluxDB2\Point;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\Collection;

#[AsTaggedItem(Consumer::PHP->value)]
class PhpConsumer implements ConsumerInterface
{
    private DeltaCalculator $deltaCalculator;

    public function __construct(DeltaCalculator $deltaCalculator)
    {
        $this->deltaCalculator = $deltaCalculator;
    }

    /**
     * @param PhpBag $bag
     */
    public function normalizeBag(MetricBagDto $bag): MetricBagDto
    {
        // for now deltas are only needed for PHP-FPM, so without php-fpm there is nothing to do..
        // it also avoids adding a null key, which would make the dashboard believe fpm is not empty (thus used)
        if ($bag->fpm->all() === []) {
            return $bag;
        }

        $data = $bag->all();

        $delta = $this->deltaCalculator->getDelta($bag);

        if ($delta) {
            $nbRequestPerSec = $delta->getPerSec('fpm.accepted-conn');

            $data['fpm']['requests-per-sec'] = $nbRequestPerSec;

            return $bag->withMetrics($data);
        }

        return $bag;
    }

    /**
     * @param PhpBag $bag
     */
    public function getMetricsToCache(MetricBagDto $bag): array
    {
        return $bag->all();
    }

    /**
     * @param PhpBag $bag
     */
    public function getInfluxPoints(MetricBagDto $bag): array
    {
        $datas = array_filter([
            'accepted_conn' => $bag->fpm->acceptedConn,
            'idle_processes' => $bag->fpm->idleProcesses,
            'active_processes' => $bag->fpm->activeProcesses,
            'slow_requests' => $bag->fpm->slowRequests,
        ], fn($v): bool => $v !== null);

        if (!$datas) {
            return [];
        }

        $point = new Point('php');

        foreach ($datas as $k => $v) {
            $point->addField($k, $v);
        }

        return [$point];
    }

    public function getConstraints(int $version): Constraint|array
    {
        return new Collection(
            fields: [
                'version' => [new Assert\Type('string'), new Assert\Length(max: 32)],
                'sapi_name' => [new Assert\Type('string'), new Assert\Length(max: 32)],
                'ini_file' => new Assert\AtLeastOneOf(constraints: [new Assert\Type('string'), new Assert\Type('bool')]),
                'ini_files' => [
                    new Assert\Type('array'),
                    new Assert\All(constraints: [new Assert\Type('string')]),
                ],
                'expose_php' => new Assert\Type('bool'),
                'memory_limit' => new Assert\Type('string'),
                'max_execution_time' => new Assert\Type('int'),
                'max_input_time' => new Assert\Type('int'),
                'max_input_vars' => new Assert\Type('int'),
                'realpath_cache_used_size' => new Assert\Type('int'),
                'realpath_cache_size' => new Assert\AtLeastOneOf([new Assert\Type('string'), new Assert\Type('integer')]),
                'realpath_cache_ttl' => new Assert\Type('int'),
                'post_max_size' => new Assert\Type('string'),
                'upload_max_filesize' => new Assert\Type('string'),
                'display_errors' => new Assert\Type('string'), /* 1 or 0, but can also be stderr */
                'display_startup_errors' => new Assert\Type('bool'),
                'log_errors' => new Assert\Type('bool'),
                'error_log' => new Assert\Type('string'),
                'error_reporting' => new Assert\Type('int'),
                'date.timezone' => new Assert\Type('string'),
                'loaded_extensions' => [
                    new Assert\Type('array'),
                    new Assert\All(constraints: [new Assert\Type('string')]),
                ],
                'apcu' => new Assert\Type('array'),
                'opcache' => new Assert\Type('array'),
                'fpm' => new Assert\Type('array'),
            ],
            allowMissingFields: true,
        );
    }
}
