<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Entity\Enums\ProjectRole;
use App\Entity\ProjectUser;
use App\Entity\User;
use App\Project\ProjectContext;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * @extends Voter<string, ProjectUser>
 */
class ProjectUserVoter extends Voter
{
    public const string DEMOTE = 'project_user.demote';
    public const string PROMOTE = 'project_user.promote';

    public function __construct(private readonly ProjectContext $projectContext) {}

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::DEMOTE, self::PROMOTE]) && $subject instanceof ProjectUser;
    }

    /**
     * @param ProjectUser $subject
     */
    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        /** @var User|null $user */
        $user = $token->getUser();

        if (!$user instanceof User) {
            $vote?->addReason('Not logged.');

            return false;
        }

        $currentRole = $user->getRoleInProject($this->projectContext->getCurrentProject());

        if (!$currentRole) {
            $vote?->addReason('Logged user is not in current project.');

            return false;
        }

        if ($currentRole->isOwner()) {
            return true;
        }

        return match ($attribute) {
            self::DEMOTE => $this->isGrantedDemote($currentRole, $subject),
            self::PROMOTE => $this->isGrantedPromote($currentRole, $subject),
            default => throw new \LogicException('This code should not be reached!'),
        };
    }

    /**
     * A user can promote another one up to the role right below their own
     */
    public function isGrantedPromote(ProjectRole $currentUserRole, ProjectUser $projectUser): bool
    {
        $promotedRole = $projectUser->getRole()->getUpgradable();

        if (!$promotedRole) {
            return false;
        }

        return $currentUserRole->isHigherThan($promotedRole);
    }

    /**
     * A user can demote (or remove) another one if they can manage them.
     * Single source of truth: ProjectRole::canManage(), shared with the controller.
     */
    public function isGrantedDemote(ProjectRole $currentUserRole, ProjectUser $projectUser): bool
    {
        return $currentUserRole->canManage($projectUser->getRole());
    }
}
