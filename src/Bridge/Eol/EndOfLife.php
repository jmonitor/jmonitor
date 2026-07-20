<?php

declare(strict_types=1);

namespace App\Bridge\Eol;

use App\Bridge\Eol\Dto\Cycle;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * In worker mode, memoizing in properties keeps EOL responses for the whole
 * process lifetime, beyond the cache's 24h TTL. Accepted trade-off: the worker
 * restarts regularly and this data changes very slowly.
 */
class EndOfLife
{
    private readonly HttpClientInterface $phpEolClient;
    private ?array $cycles = null;
    private array $eolResponse = [];
    private readonly CacheInterface $cache;

    public function __construct(HttpClientInterface $phpEolClient, CacheInterface $cache)
    {
        $this->phpEolClient = $phpEolClient;
        $this->cache = $cache;
    }

    public function getCycle(string $version, string $eolUrl, ?int $nbParts = 2): ?Cycle
    {
        $cycleAsString = $this->getCycleAsString($version, $nbParts);

        if (isset($this->cycles[$eolUrl][$cycleAsString])) {
            return $this->cycles[$eolUrl][$cycleAsString];
        }

        if (!isset($this->eolResponse[$eolUrl])) {
            $this->load($eolUrl);
        }

        if (isset($this->eolResponse[$eolUrl][$cycleAsString])) {
            return $this->cycles[$eolUrl][$cycleAsString] = Cycle::fromEolResponse($this->eolResponse[$eolUrl][$cycleAsString]);
        }

        return null;
    }

    private function load(string $url): void
    {
        $this->eolResponse[$url] = $this->cache->get('EOL_RESPONSE_' . md5($url), function (ItemInterface $item) use ($url) {
            $item->expiresAfter(3600 * 24);

            $response = $this->phpEolClient->request('GET', $url)->toArray();

            $output = [];

            foreach ($response as $version) {
                $output[$version['cycle']] = $version;
            }

            return $output;
        });
    }

    /**
     * Returns the first x parts of the version number,
     * e.g. "8.5" for "8.5.3",
     * e.g. "8.11" for "8.11.34".
     */
    private function getCycleAsString(string $version, int $nbParts): string
    {
        $parts = explode('.', $version);

        return implode('.', array_slice($parts, 0, $nbParts));
    }
}
