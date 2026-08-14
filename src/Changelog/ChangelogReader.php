<?php

declare(strict_types=1);

namespace App\Changelog;

use App\Plan\Edition;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * Release notes of the running application, read from the CHANGELOG.md shipped
 * alongside it. Only tagged versions are exposed: "Unreleased" names nothing a
 * user could be running.
 */
readonly class ChangelogReader
{
    private const int MAX_RELEASES = 3;
    private const int TTL = 300;

    public function __construct(
        #[Autowire('%kernel.project_dir%/CHANGELOG.md')]
        private string $path,
        private Edition $edition,
        private CacheInterface $cache,
    ) {}

    /**
     * @return Release[] most recent first
     */
    public function read(): array
    {
        return $this->cache->get('jmonitor_changelog_' . $this->edition->value, function (ItemInterface $item): array {
            $item->expiresAfter(self::TTL);

            return $this->parse();
        });
    }

    /**
     * @return Release[]
     */
    private function parse(): array
    {
        if (!is_file($this->path)) {
            return [];
        }

        $content = file_get_contents($this->path);

        if ($content === false) {
            return [];
        }

        $releases = [];

        // The first chunk is everything before the first version heading: the preamble.
        foreach (array_slice(preg_split('/^## /m', $content) ?: [], 1) as $block) {
            if (!preg_match('/^\[([^\]]+)\](?:\s*-\s*(\S+))?/', $block, $heading)) {
                continue;
            }

            if (strcasecmp($heading[1], 'Unreleased') === 0) {
                continue;
            }

            $groups = $this->groups($block);

            if ($groups === []) {
                continue;
            }

            $releases[] = new Release($heading[1], $this->date($heading[2] ?? null), $groups);

            if (count($releases) === self::MAX_RELEASES) {
                break;
            }
        }

        return $releases;
    }

    /**
     * @return ReleaseGroup[]
     */
    private function groups(string $block): array
    {
        $groups = [];

        foreach ($this->collect($block) as $title => $entries) {
            $kept = array_values(array_filter(array_map($this->forThisEdition(...), $entries)));

            if ($kept !== []) {
                $groups[] = new ReleaseGroup((string) $title, $kept);
            }
        }

        return $groups;
    }

    /**
     * Bullets of one version, keyed by the heading they sit under.
     *
     * @return array<string, string[]>
     */
    private function collect(string $block): array
    {
        $entries = [];
        $title = '';
        $open = false;

        foreach (explode("\n", $block) as $line) {
            $line = trim($line);

            if ($line === '') {
                $open = false;
                continue;
            }

            if (preg_match('/^###\s+(.+)$/', $line, $matches)) {
                $title = trim($matches[1]);
                $open = false;
                continue;
            }

            if (preg_match('/^[-*]\s+(.+)$/', $line, $matches)) {
                $entries[$title][] = trim($matches[1]);
                $open = true;
                continue;
            }

            if ($open) {
                $last = array_key_last($entries[$title]);
                $entries[$title][$last] .= ' ' . $line;
            }
        }

        return $entries;
    }

    /**
     * A trailing "(cloud)" or "(self-hosted)" restricts an entry to that edition.
     * The marker is dropped once it has done its job.
     */
    private function forThisEdition(string $entry): ?string
    {
        if (!preg_match('/^(.*?)\s*\((cloud|self-hosted)\)$/', $entry, $matches)) {
            return $entry;
        }

        $marker = $matches[2] === 'cloud' ? Edition::CLOUD : Edition::SELF_HOSTED;

        return $marker === $this->edition ? $matches[1] : null;
    }

    private function date(?string $date): ?\DateTimeImmutable
    {
        if ($date === null) {
            return null;
        }

        return \DateTimeImmutable::createFromFormat('!Y-m-d', $date) ?: null;
    }
}
