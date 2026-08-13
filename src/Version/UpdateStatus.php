<?php

declare(strict_types=1);

namespace App\Version;

/**
 * Where the running instance stands against the latest published release.
 * "Unknown" covers both an unversioned build and an unreachable GitHub: in
 * either case there is nothing to tell the user.
 */
readonly class UpdateStatus
{
    private function __construct(
        public bool $known,
        public bool $upToDate,
        public ?LatestRelease $update,
    ) {}

    public static function unknown(): self
    {
        return new self(false, false, null);
    }

    public static function upToDate(): self
    {
        return new self(true, true, null);
    }

    public static function updateAvailable(LatestRelease $update): self
    {
        return new self(true, false, $update);
    }
}
