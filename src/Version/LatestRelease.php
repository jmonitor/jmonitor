<?php

declare(strict_types=1);

namespace App\Version;

/**
 * A release published on GitHub.
 */
readonly class LatestRelease
{
    public function __construct(
        public string $version,
        public string $url,
    ) {}
}
