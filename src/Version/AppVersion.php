<?php

declare(strict_types=1);

namespace App\Version;

use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Version of the running application, sealed into the self-hosted Docker image
 * at build time. Anywhere else (cloud, local dev, CI builds) it stays "dev":
 * the cloud edition deploys from source and has no version to show.
 */
readonly class AppVersion
{
    private string $version;

    public function __construct(
        #[Autowire('%app.version%')]
        string $version,
    ) {
        $this->version = ltrim($version, 'v');
    }

    public function get(): string
    {
        return $this->version;
    }

    /**
     * Whether this build corresponds to a published release, and can therefore be
     * compared to the latest one.
     */
    public function isRelease(): bool
    {
        return $this->version !== '' && $this->version !== 'dev';
    }
}
