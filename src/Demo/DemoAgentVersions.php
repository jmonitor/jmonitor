<?php

declare(strict_types=1);

namespace App\Demo;

use Composer\InstalledVersions;

/**
 * The versions the demo agent advertises. It pretends to be a real agent, and this app
 * runs the very packages one would: it advertises the versions installed here.
 */
readonly class DemoAgentVersions
{
    private const string COLLECTOR = 'jmonitor/collector';
    private const string BUNDLE = 'jmonitor/jmonitor-bundle';

    public function collector(): string
    {
        return $this->get(self::COLLECTOR) ?? 'unknown';
    }

    public function bundle(): ?string
    {
        return $this->get(self::BUNDLE);
    }

    private function get(string $package): ?string
    {
        try {
            return InstalledVersions::getPrettyVersion($package) ?: null;
        } catch (\OutOfBoundsException) {
            return null;
        }
    }
}
