<?php

declare(strict_types=1);

namespace App\Tests\Version;

use App\Version\ReleaseFetcher;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ReleaseFetcherTest extends TestCase
{
    private function fetcher(HttpClientInterface $client): ReleaseFetcher
    {
        return new ReleaseFetcher($client, new ArrayAdapter(), new NullLogger());
    }

    public function testItReadsTheLatestRelease(): void
    {
        $client = new MockHttpClient(new MockResponse(json_encode([
            'tag_name' => 'v1.2.0',
            'html_url' => 'https://github.com/jmonitor/jmonitor/releases/tag/v1.2.0',
        ], JSON_THROW_ON_ERROR)));

        $release = $this->fetcher($client)->fetch('jmonitor/jmonitor');

        $this->assertNotNull($release);
        $this->assertSame('1.2.0', $release->version);
        $this->assertSame('https://github.com/jmonitor/jmonitor/releases/tag/v1.2.0', $release->url);
    }

    public function testAMissingReleaseYieldsNothing(): void
    {
        $client = new MockHttpClient(new MockResponse('{"message":"Not Found"}', ['http_code' => 404]));

        $this->assertNull($this->fetcher($client)->fetch('jmonitor/jmonitor'));
    }

    public function testBeingRateLimitedYieldsNothing(): void
    {
        $client = new MockHttpClient(new MockResponse('{"message":"API rate limit exceeded"}', ['http_code' => 403]));

        $this->assertNull($this->fetcher($client)->fetch('jmonitor/jmonitor'));
    }

    public function testAnUnparsableBodyYieldsNothing(): void
    {
        $client = new MockHttpClient(new MockResponse('<html>not json</html>'));

        $this->assertNull($this->fetcher($client)->fetch('jmonitor/jmonitor'));
    }

    public function testAResponseWithoutATagYieldsNothing(): void
    {
        $client = new MockHttpClient(new MockResponse('{"html_url":"https://example.com"}'));

        $this->assertNull($this->fetcher($client)->fetch('jmonitor/jmonitor'));
    }

    /**
     * An instance with no outbound access must see no error at all — only the
     * absence of a result.
     */
    public function testAnUnreachableHostYieldsNothing(): void
    {
        $client = new MockHttpClient(static function (): never {
            throw new TransportException('Could not resolve host');
        });

        $this->assertNull($this->fetcher($client)->fetch('jmonitor/jmonitor'));
    }

    /**
     * Failures are cached too, so a private instance retries on a timer instead of
     * on every dashboard render.
     */
    public function testAFailureIsNotRetriedOnTheNextCall(): void
    {
        $client = new MockHttpClient(new MockResponse('', ['http_code' => 500]));
        $fetcher = $this->fetcher($client);

        $fetcher->fetch('jmonitor/jmonitor');
        $fetcher->fetch('jmonitor/jmonitor');

        $this->assertSame(1, $client->getRequestsCount());
    }

    public function testASuccessIsNotRefetchedOnTheNextCall(): void
    {
        $client = new MockHttpClient(new MockResponse(json_encode([
            'tag_name' => 'v1.2.0',
            'html_url' => 'https://example.com',
        ], JSON_THROW_ON_ERROR)));
        $fetcher = $this->fetcher($client);

        $fetcher->fetch('jmonitor/jmonitor');
        $fetcher->fetch('jmonitor/jmonitor');

        $this->assertSame(1, $client->getRequestsCount());
    }

    public function testItAsksGithubForTheGivenRepository(): void
    {
        $response = new MockResponse('{"tag_name":"v2.1.0","html_url":"https://example.com"}');

        $this->fetcher(new MockHttpClient($response))->fetch('jmonitor/collector');

        $this->assertSame('https://api.github.com/repos/jmonitor/collector/releases/latest', $response->getRequestUrl());
    }

    /**
     * The app and the collector are released on their own schedules: one cache entry
     * each, or whichever is fetched first would answer for both.
     */
    public function testEachRepositoryGetsItsOwnCacheEntry(): void
    {
        $client = new MockHttpClient([
            new MockResponse('{"tag_name":"v1.2.0","html_url":"https://example.com/app"}'),
            new MockResponse('{"tag_name":"v2.1.0","html_url":"https://example.com/collector"}'),
        ]);
        $fetcher = $this->fetcher($client);

        $app = $fetcher->fetch('jmonitor/jmonitor');
        $collector = $fetcher->fetch('jmonitor/collector');

        $this->assertSame('1.2.0', $app?->version);
        $this->assertSame('2.1.0', $collector?->version);
    }
}
