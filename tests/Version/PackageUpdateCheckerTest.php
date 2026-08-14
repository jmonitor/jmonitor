<?php

declare(strict_types=1);

namespace App\Tests\Version;

use App\Version\AdvertisedVersion;
use App\Version\Package;
use App\Version\PackageUpdateChecker;
use App\Version\ReleaseFetcher;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

class PackageUpdateCheckerTest extends TestCase
{
    private MockHttpClient $client;

    private MockResponse $response;

    private function checker(?string $latestTag): PackageUpdateChecker
    {
        $this->response = $latestTag === null
            ? new MockResponse('', ['http_code' => 500])
            : new MockResponse(json_encode([
                'tag_name' => $latestTag,
                'html_url' => 'https://github.com/jmonitor/collector/releases/tag/' . $latestTag,
            ], JSON_THROW_ON_ERROR));

        $this->client = new MockHttpClient($this->response);

        return new PackageUpdateChecker(new ReleaseFetcher($this->client, new ArrayAdapter(), new NullLogger()));
    }

    public function testAnOlderPackageHasAnUpdateAvailable(): void
    {
        $status = $this->checker('v2.1.0')->check(new AdvertisedVersion(Package::COLLECTOR, 'v2.0.1'));

        $this->assertTrue($status->known);
        $this->assertFalse($status->upToDate);
        $this->assertNotNull($status->update);
        $this->assertSame('2.1.0', $status->update->version);
        $this->assertSame('https://github.com/jmonitor/collector/releases/tag/v2.1.0', $status->update->url);
    }

    public function testTheLatestPackageIsUpToDate(): void
    {
        $status = $this->checker('v2.1.0')->check(new AdvertisedVersion(Package::COLLECTOR, 'v2.1.0'));

        $this->assertTrue($status->known);
        $this->assertTrue($status->upToDate);
        $this->assertNull($status->update);
    }

    /**
     * A package installed from a development branch can be ahead of the latest tag.
     */
    public function testANewerPackageIsUpToDate(): void
    {
        $this->assertTrue($this->checker('v2.1.0')->check(new AdvertisedVersion(Package::COLLECTOR, '2.2.0'))->upToDate);
    }

    /**
     * Every collector below 2.1 advertises "1.0" whatever it runs. The version cannot
     * be compared, but it is known to predate 2.1, so an update certainly exists.
     */
    public function testALegacyCollectorHasAnUpdateAvailableWithoutBeingCompared(): void
    {
        $status = $this->checker('v2.1.0')->check(new AdvertisedVersion(Package::COLLECTOR, '1.0'));

        $this->assertTrue($status->known);
        $this->assertFalse($status->upToDate);
        $this->assertSame('2.1.0', $status->update?->version);
    }

    public function testEachPackageIsComparedToItsOwnRepository(): void
    {
        $this->checker('v2.1.0')->check(new AdvertisedVersion(Package::BUNDLE, '2.0.1'));

        $this->assertSame(
            'https://api.github.com/repos/jmonitor/jmonitor-bundle/releases/latest',
            $this->response->getRequestUrl(),
        );
    }

    public function testAPackageThatCouldNotResolveItselfIsLeftAlone(): void
    {
        $status = $this->checker('v2.1.0')->check(new AdvertisedVersion(Package::BUNDLE, 'unknown'));

        $this->assertFalse($status->known);
        $this->assertNull($status->update);
    }

    public function testAnAbsentPackageIsLeftAlone(): void
    {
        $status = $this->checker('v2.1.0')->check(new AdvertisedVersion(Package::BUNDLE, null));

        $this->assertFalse($status->known);
        $this->assertNull($status->update);
    }

    /**
     * Nothing can be said about a version we cannot read, so GitHub is not even called.
     */
    public function testAnUnreadableVersionTriggersNoOutboundCall(): void
    {
        $this->checker('v2.1.0')->check(new AdvertisedVersion(Package::BUNDLE, null));

        $this->assertSame(0, $this->client->getRequestsCount());
    }

    public function testAnUnreachableGithubLeavesTheStatusUnknown(): void
    {
        $status = $this->checker(null)->check(new AdvertisedVersion(Package::COLLECTOR, '2.0.1'));

        $this->assertFalse($status->known);
        $this->assertFalse($status->upToDate);
        $this->assertNull($status->update);
    }

    public function testAnUnreachableGithubLeavesALegacyCollectorUnknownToo(): void
    {
        $status = $this->checker(null)->check(new AdvertisedVersion(Package::COLLECTOR, '1.0'));

        $this->assertFalse($status->known);
        $this->assertNull($status->update);
    }
}
