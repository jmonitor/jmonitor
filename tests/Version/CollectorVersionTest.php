<?php

declare(strict_types=1);

namespace App\Tests\Version;

use App\Version\CollectorVersion;
use PHPUnit\Framework\TestCase;

class CollectorVersionTest extends TestCase
{
    public function testAReadableVersionIsKnown(): void
    {
        $version = new CollectorVersion('2.1.0');

        $this->assertTrue($version->isKnown());
        $this->assertFalse($version->isLegacy());
        $this->assertSame('2.1.0', $version->get());
        $this->assertSame('2.1.0', $version->display());
    }

    /**
     * Composer reports the tag as it was written, "v" included.
     */
    public function testTheTagPrefixIsStripped(): void
    {
        $this->assertSame('2.1.0', (new CollectorVersion('v2.1.0'))->get());
    }

    public function testTheHardcodedVersionIsLegacy(): void
    {
        $version = new CollectorVersion('1.0');

        $this->assertTrue($version->isLegacy());
        $this->assertFalse($version->isKnown());
    }

    /**
     * Displaying "1.0" would pass off the never-bumped constant as a real version.
     */
    public function testALegacyVersionIsNotDisplayedAsANumber(): void
    {
        $this->assertSame('unknown', (new CollectorVersion('1.0'))->display());
    }

    public function testACollectorThatCouldNotResolveItselfIsNeitherKnownNorLegacy(): void
    {
        $version = new CollectorVersion('unknown');

        $this->assertFalse($version->isKnown());
        $this->assertFalse($version->isLegacy());
        $this->assertSame('unknown', $version->display());
    }

    public function testAProjectWithoutAPushIsNeitherKnownNorLegacy(): void
    {
        $version = new CollectorVersion(null);

        $this->assertFalse($version->isKnown());
        $this->assertFalse($version->isLegacy());
        $this->assertSame('unknown', $version->display());
    }

    public function testAnEmptyHeaderIsNeitherKnownNorLegacy(): void
    {
        $version = new CollectorVersion('');

        $this->assertFalse($version->isKnown());
        $this->assertFalse($version->isLegacy());
    }
}
