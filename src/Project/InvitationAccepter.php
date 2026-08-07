<?php

declare(strict_types=1);

namespace App\Project;

use App\Entity\Enums\UserStatus;
use App\Entity\ProjectInvitation;
use App\Entity\ProjectUser;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Single path turning a pending invitation into a project membership: used by
 * the in-app accept action and by registration through an invitation link.
 */
final readonly class InvitationAccepter
{
    public function __construct(private EntityManagerInterface $em) {}

    public function accept(ProjectInvitation $invitation, User $user): ProjectUser
    {
        $project = $invitation->getProject();
        $role = $invitation->getRole();

        // Both columns are nullable: false — a persisted invitation always has them.
        \assert($project !== null && $role !== null);

        $projectUser = new ProjectUser();
        $projectUser->setUser($user);
        $projectUser->setProject($project);
        $projectUser->setRole($role);

        $user->setStatus(UserStatus::ACTIVE);

        $this->em->persist($projectUser);
        $this->em->remove($invitation);
        $this->em->flush();

        return $projectUser;
    }
}
