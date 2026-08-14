<?php

declare(strict_types=1);

namespace App\Version;

readonly class CollectorUpdateChecker
{
    private const string REPOSITORY = 'jmonitor/collector';

    public function __construct(
        private ReleaseFetcher $releaseFetcher,
    ) {}

    public function check(CollectorVersion $version): UpdateStatus
    {
        if (!$version->isKnown() && !$version->isLegacy()) {
            return UpdateStatus::unknown();
        }

        $latest = $this->releaseFetcher->fetch(self::REPOSITORY);

        if (!$latest) {
            return UpdateStatus::unknown();
        }

        if ($version->isLegacy()) {
            return UpdateStatus::updateAvailable($latest);
        }

        return version_compare($version->get(), $latest->version, '<')
            ? UpdateStatus::updateAvailable($latest)
            : UpdateStatus::upToDate();
    }
}
