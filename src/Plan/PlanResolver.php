<?php

declare(strict_types=1);

namespace App\Plan;

use App\Entity\Enums\Plan;
use App\Entity\Project;

/**
 * Resolves the effective plan of a project according to the edition (APP_EDITION):
 * cloud → plan of the active Subscription (FREE by default),
 * self-hosted → unlimited plan, whatever the Subscription (inert).
 *
 * Always go through this service (and not Project::getCurrentPlan())
 * to gate a feature or a limit.
 */
readonly class PlanResolver
{
    public function __construct(
        private Edition $edition,
    ) {}

    public function resolve(Project $project): Plan
    {
        return match ($this->edition) {
            Edition::CLOUD => $project->getCurrentPlan(),
            Edition::SELF_HOSTED => Plan::SELF_HOSTED,
        };
    }
}
