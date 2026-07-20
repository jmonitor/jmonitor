<?php

declare(strict_types=1);

namespace App\Metrics;

use App\Entity\Enums\Component;
use App\Metrics\Consumer\Consumer;
use App\Metrics\Dto\MetricBagDto;
use App\Project\ProjectContext;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Contracts\Service\ResetInterface;
use Twig\Attribute\AsTwigFunction;

class MetricsBagProvider implements ResetInterface
{
    /**
     * @var MetricBagDto[]
     */
    private array $propertyCache = [];

    public function __construct(
        private readonly CacheItemPoolInterface $cache,
        private readonly ProjectContext $projectContext,
    ) {}

    public function saveMetricBag(MetricBagDto $bag, int $expireAfter = 40, bool $deferred = false): void
    {
        $item = $this->cache->getItem($bag->getConsumer()->getCacheKey($bag->getProject()))->set([
            'consumer' => $bag->getConsumer()->value,
            'metrics' => $bag->all(),
            'version' => $bag->getVersion(),
            'received_at' => $bag->getReceivedAt(),
            'threw' => $bag->hasThrew(),
        ])->expiresAfter($expireAfter);

        if ($deferred) {
            $this->cache->saveDeferred($item);
        } else {
            $this->cache->save($item);
        }
    }

    public function commit(): void
    {
        $this->cache->commit();
    }

    /**
     * @template T of MetricBagDto
     * @param class-string<T>|null $returnClass
     * @return T|null
     */
    #[AsTwigFunction('bag')]
    public function getLastBag(Consumer|string $consumer, ?string $returnClass = null): ?MetricBagDto
    {
        $consumer = is_string($consumer) ? Consumer::from($consumer) : $consumer;

        $key = $consumer->getCacheKey($this->projectContext->getCurrentProject());

        if (array_key_exists($key, $this->propertyCache)) {
            return $this->propertyCache[$key];
        }

        $datas = $this->cache->getItem($key)->get();

        if (!$datas) {
            return $this->propertyCache[$key] = null;
        }

        return $this->propertyCache[$key] = $this->createBag($datas);
    }

    /**
     * @return array<string, MetricBagDto>|null
     */
    public function getComponentBags(Component $component): ?array
    {
        $bags = [];

        foreach ($component->consumers() as $consumer) {
            $bags[$consumer->value] = $this->getLastBag($consumer);
        }

        return array_filter($bags);
    }

    public function reset(): void
    {
        $this->propertyCache = [];
    }

    /**
     * @param mixed[] $datas
     */
    private function createBag(array $datas): MetricBagDto
    {
        return MetricBagDto::create($this->projectContext->getCurrentProject(), Consumer::from($datas['consumer']), $datas['version'], $datas['metrics'], $datas['received_at'], $datas['threw'] ?? false);
    }
}
