<?php

declare(strict_types=1);

namespace App\Metrics;

use App\Metrics\Dto\DeltaBag;
use App\Metrics\Dto\MetricBagDto;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Contracts\Service\ResetInterface;

/**
 * Computes value deltas from one push to the next.
 * Provides both delta values and per-second values.
 */
class DeltaCalculator implements ResetInterface
{
    /** @var array<string, DeltaBag|null> */
    private array $propertyCache = [];

    public function __construct(
        private readonly CacheItemPoolInterface $cache,
    ) {}

    public function getDelta(MetricBagDto $bag): ?DeltaBag
    {
        $propertyCacheKey = sprintf('%s_%s_%s', $bag->getConsumer()->value, $bag->getVersion(), $bag->getProject()->getId());

        if (array_key_exists($propertyCacheKey, $this->propertyCache)) {
            return $this->propertyCache[$propertyCacheKey];
        }

        $cacheKey = 'DELTA_' . $propertyCacheKey;
        $item = $this->cache->getItem($cacheKey);
        $previousData = $item->get();

        $currentTimestamp = $bag->getReceivedAt()->getTimestamp();
        $currentValues = $this->flatten($bag->all());

        $item->set([
            'timestamp' => $currentTimestamp,
            'values' => $currentValues,
        ]);
        $item->expiresAfter(60);
        $this->cache->save($item);

        if ($previousData === null) {
            return $this->propertyCache[$propertyCacheKey] = null;
        }

        $elapsedTime = $currentTimestamp - $previousData['timestamp'];

        // Guard against inconsistent data
        if ($elapsedTime <= 0) {
            return $this->propertyCache[$propertyCacheKey] = null;
        }

        return $this->propertyCache[$propertyCacheKey] = new DeltaBag($previousData['values'], $currentValues, $elapsedTime);
    }

    public function reset(): void
    {
        $this->propertyCache = [];
    }

    /**
     * @param array<mixed> $array
     * @return array<string, mixed>
     */
    private function flatten(array $array, string $prefix = ''): array
    {
        $result = [];
        foreach ($array as $key => $value) {
            $newKey = $prefix === '' ? (string) $key : $prefix . '.' . $key;
            if (is_array($value)) {
                $result = array_merge($result, $this->flatten($value, $newKey));
            } else {
                $result[$newKey] = $value;
            }
        }

        return $result;
    }
}
