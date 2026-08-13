<?php

declare(strict_types=1);

namespace App\Version;

readonly class UpdateChecker
{
    public function __construct(
        private AppVersion $appVersion,
        private ReleaseFetcher $releaseFetcher,
    ) {}

    public function check(): UpdateStatus
    {
        if (!$this->appVersion->isRelease()) {
            return UpdateStatus::unknown();
        }

        $latest = $this->releaseFetcher->fetch();

        if (!$latest) {
            return UpdateStatus::unknown();
        }

        return version_compare($this->appVersion->get(), $latest->version, '<')
            ? UpdateStatus::updateAvailable($latest)
            : UpdateStatus::upToDate();
    }
}
