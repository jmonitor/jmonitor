<?php

declare(strict_types=1);

namespace App\Project;

use App\Entity\Enums\ProjectRole;
use App\Entity\Project;
use App\Entity\ProjectUser;
use App\Entity\User;
use App\Plan\Edition;
use App\Subscription\TrialSubscriptionService;
use Doctrine\ORM\EntityManagerInterface;

final readonly class ProjectCreator
{
    public function __construct(
        private EntityManagerInterface $em,
        private TrialSubscriptionService $trialSubscriptionService,
        private Edition $edition,
    ) {}

    public function create(Project $project, User $owner): void
    {
        $projectUser = new ProjectUser();
        $projectUser->setUser($owner);
        $projectUser->setRole(ProjectRole::OWNER);
        $project->addProjectUser($projectUser);

        $this->em->persist($project);
        $this->em->persist($projectUser);

        // The trial only makes sense in cloud: in self-hosted the plan comes from
        // the PlanResolver, a Subscription would just be junk data.
        if ($this->edition->isCloud()) {
            $this->trialSubscriptionService->grantTrial($project);
        }

        $this->em->flush();
    }
}
