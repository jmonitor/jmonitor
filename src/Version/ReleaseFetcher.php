<?php

declare(strict_types=1);

namespace App\Version;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Latest release of a JMonitor repository published on GitHub.
 *
 * Every failure is swallowed and returns null: an instance without outbound
 * access must see no error, only the absence of a result. Failures are cached
 * briefly so such an instance retries hourly rather than on every render.
 */
readonly class ReleaseFetcher
{
    private const string URL = 'https://api.github.com/repos/%s/releases/latest';
    private const int TTL_SUCCESS = 86400;
    private const int TTL_FAILURE = 3600;

    public function __construct(
        private HttpClientInterface $httpClient,
        private CacheInterface $cache,
        private LoggerInterface $logger,
    ) {}

    /**
     * @param string $repository "owner/name" on GitHub
     */
    public function fetch(string $repository): ?LatestRelease
    {
        $key = 'jmonitor_latest_release_' . str_replace('/', '.', $repository);

        return $this->cache->get($key, function (ItemInterface $item) use ($repository): ?LatestRelease {
            $item->expiresAfter(self::TTL_FAILURE);

            try {
                $response = $this->httpClient->request('GET', sprintf(self::URL, $repository), [
                    'timeout' => 3,
                    'headers' => ['Accept' => 'application/vnd.github+json'],
                ])->toArray();
            } catch (\Throwable $e) {
                $this->logger->info('Could not fetch the latest release of {repository}.', [
                    'repository' => $repository,
                    'exception' => $e,
                ]);

                return null;
            }

            $tag = $response['tag_name'] ?? null;
            $url = $response['html_url'] ?? null;

            if (!is_string($tag) || !is_string($url)) {
                return null;
            }

            $item->expiresAfter(self::TTL_SUCCESS);

            return new LatestRelease(ltrim($tag, 'v'), $url);
        });
    }
}
