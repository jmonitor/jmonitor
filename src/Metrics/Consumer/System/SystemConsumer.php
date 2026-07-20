<?php

declare(strict_types=1);

namespace App\Metrics\Consumer\System;

use App\Metrics\Consumer\Consumer;
use App\Metrics\Consumer\ConsumerInterface;
use App\Metrics\Dto\Bag\System\SystemBag;
use App\Metrics\Dto\MetricBagDto;
use InfluxDB2\Point;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\Collection;

#[AsTaggedItem(Consumer::SYSTEM->value)]
class SystemConsumer implements ConsumerInterface
{
    public function normalizeBag(MetricBagDto $bag): MetricBagDto
    {
        return $bag;
    }

    /**
     * @param SystemBag $bag
     */
    public function getMetricsToCache(MetricBagDto $bag): array
    {
        return $bag->all();
    }

    /**
     * @param SystemBag $bag
     */
    public function getInfluxPoints(MetricBagDto $bag): array
    {
        $datas = array_filter([
            'disk_used_percent' => $bag->disk->usedPercent,
            'cpu_load' => $bag->cpu->usedPercent,
            'ram_used_percent' => $bag->ram->usedPercent,
        ], fn($v): bool => $v !== null);

        if (!$datas) {
            return [];
        }

        $point = new Point('system');

        foreach ($datas as $k => $v) {
            $point->addField($k, $v);
        }

        return [$point];
    }

    public function getConstraints(int $version): Constraint|array
    {
        return new Collection(
            fields: [
                'disk' => new Collection(
                    fields: [
                        'total' => [new Assert\Type('int'), new Assert\GreaterThanOrEqual(0)],
                        'free' => [new Assert\Type('int'), new Assert\GreaterThanOrEqual(0)],
                    ],
                ),
                'cpu' => new Collection(
                    fields: [
                        'cores' => [new Assert\Type('int'), new Assert\GreaterThanOrEqual(1)],
                        'load' => new Assert\Type('int'),
                        'load1' => new Assert\Type(type: ['float', 'int']),
                        'load5' => new Assert\Type(type: ['float', 'int']),
                        'load15' => new Assert\Type(type: ['float', 'int']),
                    ],
                ),
                'ram' => new Collection(
                    fields: [
                        'total' => [new Assert\Type('int'), new Assert\GreaterThanOrEqual(0)],
                        'available' => [new Assert\Type('int'), new Assert\GreaterThanOrEqual(0)],
                    ],
                ),
                'os' => new Collection(
                    fields: [
                        'pretty_name' => [new Assert\Type('string'), new Assert\Length(max: 255)],
                        'uptime' => [new Assert\Type('int'), new Assert\GreaterThanOrEqual(0)],
                    ],
                ),
                'time' => new Assert\Type('int'),
                'timezone' => [new Assert\Type('string'), new Assert\Length(max: 64)],
                'hostname' => [new Assert\Type('string'), new Assert\Length(max: 255)],
            ],
            allowMissingFields: true,
        );
    }
}
