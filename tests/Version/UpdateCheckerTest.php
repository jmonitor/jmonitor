<?php

declare(strict_types=1);

namespace App\Tests\Version;

use App\Version\AppVersion;
use App\Version\ReleaseFetcher;
use App\Version\UpdateChecker;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

class UpdateCheckerTest extends TestCase
{
    private function checker(string $installed, ?string $latestTag): UpdateChecker
    {
        $response = $latestTag === null
            ? new MockResponse('', ['http_code' => 500])
            : new MockResponse(json_encode([
                'tag_name' => $latestTag,
                'html_url' => 'https://github.com/jmonitor/jmonitor/releases/tag/' . $latestTag,
            ], JSON_THROW_ON_ERROR));

        $fetcher = new ReleaseFetcher(new MockHttpClient($response), new ArrayAdapter(), new NullLogger());

        return new UpdateChecker(new AppVersion($installed), $fetcher);
    }

    public function testAnOlderInstallationHasAnUpdateAvailable(): void
    {
        $status = $this->checker('1.0.0', 'v1.2.0')->check();

        $this->assertTrue($status->known);
        $this->assertFalse($status->upToDate);
        $this->assertNotNull($status->update);
        $this->assertSame('1.2.0', $status->update->version);
    }

    public function testTheSameVersionIsUpToDate(): void
    {
        $status = $this->checker('1.2.0', 'v1.2.0')->check();

        $this->assertTrue($status->known);
        $this->assertTrue($status->upToDate);
        $this->assertNull($status->update);
    }

    /**
     * An image built from master can be ahead of the latest release: that is not
     * an update to offer.
     */
    public function testANewerInstallationIsUpToDate(): void
    {
        $this->assertTrue($this->checker('1.3.0', 'v1.2.0')->check()->upToDate);
    }

    public function testTheTagPrefixIsIrrelevantOnBothSides(): void
    {
        $this->assertTrue($this->checker('v1.2.0', '1.2.0')->check()->upToDate);
    }

    public function testAnUnversionedBuildIsNeverCompared(): void
    {
        $status = $this->checker('dev', 'v1.2.0')->check();

        $this->assertFalse($status->known);
        $this->assertNull($status->update);
    }

    public function testAnUnreachableGithubLeavesTheStatusUnknown(): void
    {
        $status = $this->checker('1.0.0', null)->check();

        $this->assertFalse($status->known);
        $this->assertFalse($status->upToDate);
        $this->assertNull($status->update);
    }
}
