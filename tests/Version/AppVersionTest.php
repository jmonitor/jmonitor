<?php

declare(strict_types=1);

namespace App\Tests\Version;

use App\Version\AppVersion;
use PHPUnit\Framework\TestCase;

class AppVersionTest extends TestCase
{
    public function testAPublishedTagIsARelease(): void
    {
        $version = new AppVersion('1.0.0');

        $this->assertSame('1.0.0', $version->get());
        $this->assertTrue($version->isRelease());
    }

    /**
     * The workflow passes metadata-action's {{version}}, which already strips the
     * "v" — but a hand-made build may pass the raw tag, and the comparison and the
     * UI both want a single form.
     */
    public function testTheTagPrefixIsStripped(): void
    {
        $this->assertSame('1.2.3', new AppVersion('v1.2.3')->get());
    }

    public function testALocalOrCiBuildIsNotARelease(): void
    {
        $version = new AppVersion('dev');

        $this->assertSame('dev', $version->get());
        $this->assertFalse($version->isRelease());
    }

    public function testNoVersionAtAllIsNotARelease(): void
    {
        $this->assertFalse(new AppVersion('')->isRelease());
    }
}
