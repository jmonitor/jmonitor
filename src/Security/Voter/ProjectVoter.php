<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Entity\Project;
use App\Entity\ProjectUser;
use App\Entity\User;
use App\Project\ProjectContext;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * @extends Voter<string, Project>
 */
class ProjectVoter extends Voter
{
    public const string PROJECT_USER = 'project.user';
    public const string PROJECT_ADMIN = 'project.admin';
    public const string PROJECT_OWNER = 'project.owner';
    public const string DELETE = 'project.delete';

    public function __construct(private readonly ProjectContext $projectContext) {}

    protected function supports(string $attribute, mixed $subject): bool
    {
        return
            in_array($attribute, [self::PROJECT_USER, self::PROJECT_ADMIN, self::PROJECT_OWNER, self::DELETE])
            && ($subject instanceof Project || $subject === null)
        ;
    }

    /**
     * @param ?Project $subject
     */
    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $subject ??= $this->projectContext->getCurrentProject();

        /** @var User|null $user */
        $user = $token->getUser();

        if (!$user instanceof User) {
            $vote?->addReason('Not logged.');

            return false;
        }

        return match ($attribute) {
            self::PROJECT_OWNER => $this->isGrantedOwner($user, $subject),
            self::PROJECT_ADMIN => $this->isGrantedAdmin($user, $subject),
            self::PROJECT_USER => $this->isGrantedMember($user, $subject),
            self::DELETE => $this->isGrantedOwner($user, $subject),
            default => throw new \LogicException('This code should not be reached!'),
        };
    }

    private function isGrantedOwner(User $user, Project $project): bool
    {
        return array_any($user->getProjectUsers(), fn(ProjectUser $projectUser): bool => $projectUser->getProject() === $project && $projectUser->getRole()->isGrantedOwner());
    }

    private function isGrantedAdmin(User $user, Project $project): bool
    {
        return array_any($user->getProjectUsers(), fn(ProjectUser $projectUser): bool => $projectUser->getProject() === $project && $projectUser->getRole()->isGrantedAdmin());
    }

    private function isGrantedMember(User $user, Project $project): bool
    {
        return array_any($user->getProjectUsers(), fn(ProjectUser $projectUser): bool => $projectUser->getProject() === $project);
    }
}
