<?php

declare(strict_types=1);

namespace App\Version;

/**
 * The version a collector advertises when it pushes metrics, which is not always
 * a version: it may say nothing, or say the one thing it always used to say.
 */
readonly class CollectorVersion
{
    private const string UNKNOWN = 'unknown';

    /**
     * Every collector below 2.1 sends this: the constant was written by hand and
     * never bumped. It tells nothing about the version running, only that it
     * predates 2.1.
     */
    private const string LEGACY = '1.0';

    private string $version;

    public function __construct(?string $version)
    {
        $this->version = ltrim($version ?? '', 'v');
    }

    public function get(): string
    {
        return $this->version;
    }

    /**
     * Whether the version can be compared to a published release.
     */
    public function isKnown(): bool
    {
        return $this->version !== '' && $this->version !== self::UNKNOWN && !$this->isLegacy();
    }

    public function isLegacy(): bool
    {
        return $this->version === self::LEGACY;
    }

    public function display(): string
    {
        return $this->isKnown() ? $this->version : self::UNKNOWN;
    }
}
