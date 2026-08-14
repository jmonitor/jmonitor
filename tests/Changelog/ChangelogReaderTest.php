<?php

declare(strict_types=1);

namespace App\Tests\Changelog;

use App\Changelog\ChangelogReader;
use App\Plan\Edition;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

class ChangelogReaderTest extends TestCase
{
    private const string CHANGELOG = <<<'MD'
        # Changelog

        All notable changes to JMonitor are documented in this file.
        Lines suffixed with `(cloud)` or `(self-hosted)` apply to that edition only.

        ## [Unreleased]

        ### Added

        - Something not released yet

        ## [2.0.0] - 2026-09-01

        ### Added

        - A feature for everyone
        - A cloud feature (cloud)
        - A self-hosted feature (self-hosted)

        ### Fixed

        - A cloud fix (cloud)

        ## [1.0.0] - 2026-08-07

        - Initial public release.

        [Unreleased]: https://github.com/jmonitor/jmonitor/compare/v2.0.0...HEAD
        [2.0.0]: https://github.com/jmonitor/jmonitor/releases/tag/v2.0.0
        MD;

    /** @var string[] */
    private array $files = [];

    protected function tearDown(): void
    {
        foreach ($this->files as $file) {
            unlink($file);
        }
    }

    private function reader(string $content, Edition $edition = Edition::CLOUD): ChangelogReader
    {
        $path = tempnam(sys_get_temp_dir(), 'changelog');
        self::assertNotFalse($path);
        file_put_contents($path, $content);
        $this->files[] = $path;

        return new ChangelogReader($path, $edition, new ArrayAdapter());
    }

    /**
     * @return string[]
     */
    private function entries(string $content, Edition $edition, string $version, string $group): array
    {
        foreach ($this->reader($content, $edition)->read() as $release) {
            if ($release->version !== $version) {
                continue;
            }

            foreach ($release->groups as $releaseGroup) {
                if ($releaseGroup->title === $group) {
                    return $releaseGroup->entries;
                }
            }
        }

        return [];
    }

    public function testUnreleasedIsSkipped(): void
    {
        $versions = array_map(
            fn($release) => $release->version,
            $this->reader(self::CHANGELOG)->read(),
        );

        $this->assertSame(['2.0.0', '1.0.0'], $versions);
    }

    public function testCloudKeepsItsOwnLinesAndTheSharedOnes(): void
    {
        $this->assertSame(
            ['A feature for everyone', 'A cloud feature'],
            $this->entries(self::CHANGELOG, Edition::CLOUD, '2.0.0', 'Added'),
        );
    }

    public function testSelfHostedKeepsItsOwnLinesAndTheSharedOnes(): void
    {
        $this->assertSame(
            ['A feature for everyone', 'A self-hosted feature'],
            $this->entries(self::CHANGELOG, Edition::SELF_HOSTED, '2.0.0', 'Added'),
        );
    }

    public function testAGroupLeftEmptyByTheFilterIsDropped(): void
    {
        $titles = [];
        foreach ($this->reader(self::CHANGELOG, Edition::SELF_HOSTED)->read() as $release) {
            if ($release->version === '2.0.0') {
                $titles = array_map(fn($group) => $group->title, $release->groups);
            }
        }

        $this->assertSame(['Added'], $titles);
    }

    public function testAVersionLeftEmptyByTheFilterIsDropped(): void
    {
        $changelog = <<<'MD'
            # Changelog

            ## [2.0.0] - 2026-09-01

            ### Added

            - A cloud feature (cloud)

            ## [1.0.0] - 2026-08-07

            - Initial public release.
            MD;

        $versions = array_map(
            fn($release) => $release->version,
            $this->reader($changelog, Edition::SELF_HOSTED)->read(),
        );

        $this->assertSame(['1.0.0'], $versions);
    }

    public function testEntriesWithoutAGroupAreKept(): void
    {
        $this->assertSame(
            ['Initial public release.'],
            $this->entries(self::CHANGELOG, Edition::CLOUD, '1.0.0', ''),
        );
    }

    public function testTheLinkDefinitionsAtTheBottomAreIgnored(): void
    {
        foreach ($this->reader(self::CHANGELOG)->read() as $release) {
            foreach ($release->groups as $group) {
                foreach ($group->entries as $entry) {
                    $this->assertStringNotContainsString('https://github.com', $entry);
                }
            }
        }
    }

    public function testTheDateIsParsed(): void
    {
        $release = $this->reader(self::CHANGELOG)->read()[0];

        $this->assertNotNull($release->date);
        $this->assertSame('2026-09-01', $release->date->format('Y-m-d'));
    }

    public function testAVersionWithoutADateIsStillRead(): void
    {
        $changelog = <<<'MD'
            # Changelog

            ## [2.0.0]

            - Something
            MD;

        $release = $this->reader($changelog)->read()[0];

        $this->assertSame('2.0.0', $release->version);
        $this->assertNull($release->date);
    }

    public function testAnEntryWrappedOverSeveralLinesIsJoined(): void
    {
        $changelog = <<<'MD'
            # Changelog

            ## [2.0.0] - 2026-09-01

            ### Added

            - A feature described over
              two lines (cloud)
            MD;

        $this->assertSame(
            ['A feature described over two lines'],
            $this->entries($changelog, Edition::CLOUD, '2.0.0', 'Added'),
        );
    }

    public function testOnlyTheThreeLatestVersionsAreKept(): void
    {
        $changelog = "# Changelog\n";
        foreach (['4.0.0', '3.0.0', '2.0.0', '1.0.0'] as $version) {
            $changelog .= "\n## [{$version}] - 2026-09-01\n\n- Something\n";
        }

        $versions = array_map(fn($release) => $release->version, $this->reader($changelog)->read());

        $this->assertSame(['4.0.0', '3.0.0', '2.0.0'], $versions);
    }

    public function testAMissingFileYieldsNothing(): void
    {
        $reader = new ChangelogReader('/nonexistent/CHANGELOG.md', Edition::CLOUD, new ArrayAdapter());

        $this->assertSame([], $reader->read());
    }
}
