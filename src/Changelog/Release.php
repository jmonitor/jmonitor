<?php

declare(strict_types=1);

namespace App\Changelog;

readonly class Release
{
    /**
     * @param ReleaseGroup[] $groups
     */
    public function __construct(
        public string $version,
        public ?\DateTimeImmutable $date,
        public array $groups,
    ) {}
}
