<?php

declare(strict_types=1);

namespace App\Tests\Version;

use App\Version\AdvertisedVersion;
use App\Version\Package;
use PHPUnit\Framework\TestCase;

class AdvertisedVersionTest extends TestCase
{
    public function testAReadableVersionIsKnown(): void
    {
        $version = new AdvertisedVersion(Package::COLLECTOR, '2.1.0');

        $this->assertTrue($version->isKnown());
        $this->assertFalse($version->isLegacy());
        $this->assertFalse($version->isAbsent());
        $this->assertSame('2.1.0', $version->get());
        $this->assertSame('2.1.0', $version->display());
    }

    /**
     * Composer reports the tag as it was written, "v" included.
     */
    public function testTheTagPrefixIsStripped(): void
    {
        $this->assertSame('2.1.0', (new AdvertisedVersion(Package::BUNDLE, 'v2.1.0'))->get());
    }

    public function testTheCollectorsHardcodedVersionIsLegacy(): void
    {
        $version = new AdvertisedVersion(Package::COLLECTOR, '1.0');

        $this->assertTrue($version->isLegacy());
        $this->assertFalse($version->isKnown());
    }

    /**
     * Displaying "1.0" would pass off the never-bumped constant as a real version.
     */
    public function testALegacyVersionIsNotDisplayedAsANumber(): void
    {
        $this->assertSame('unknown', (new AdvertisedVersion(Package::COLLECTOR, '1.0'))->display());
    }

    /**
     * The quirk belongs to the collector alone: "1.0" from anything else is a version
     * like any other.
     */
    public function testOnlyTheCollectorHasALegacyVersion(): void
    {
        $version = new AdvertisedVersion(Package::BUNDLE, '1.0');

        $this->assertFalse($version->isLegacy());
        $this->assertTrue($version->isKnown());
        $this->assertSame('1.0', $version->display());
    }

    public function testAPackageThatCouldNotResolveItselfIsNeitherKnownNorAbsent(): void
    {
        $version = new AdvertisedVersion(Package::BUNDLE, 'unknown');

        $this->assertFalse($version->isKnown());
        $this->assertFalse($version->isLegacy());
        $this->assertFalse($version->isAbsent());
        $this->assertSame('unknown', $version->display());
    }

    /**
     * Absent is not the same as unresolved: an agent with no bundle at all sends no
     * header, and has nothing to show.
     */
    public function testAMissingHeaderIsAbsent(): void
    {
        $version = new AdvertisedVersion(Package::BUNDLE, null);

        $this->assertTrue($version->isAbsent());
        $this->assertFalse($version->isKnown());
        $this->assertSame('unknown', $version->display());
    }

    public function testAnEmptyHeaderIsAbsent(): void
    {
        $this->assertTrue((new AdvertisedVersion(Package::BUNDLE, ''))->isAbsent());
    }

    public function testEachPackageKnowsItsRepository(): void
    {
        $this->assertSame('jmonitor/collector', Package::COLLECTOR->repository());
        $this->assertSame('jmonitor/jmonitor-bundle', Package::BUNDLE->repository());
    }
}
