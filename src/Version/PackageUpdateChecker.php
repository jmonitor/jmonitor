<?php

declare(strict_types=1);

namespace App\Version;

readonly class PackageUpdateChecker
{
    public function __construct(
        private ReleaseFetcher $releaseFetcher,
    ) {}

    public function check(AdvertisedVersion $version): UpdateStatus
    {
        if (!$version->isKnown() && !$version->isLegacy()) {
            return UpdateStatus::unknown();
        }

        $latest = $this->releaseFetcher->fetch($version->package->repository());

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
