<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Entity\Enums\Plan;
use App\Entity\Project;
use App\Plan\PlanResolver;
use App\Project\ProjectContext;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * @extends Voter<string, ?Project>
 */
class PlanVoter extends Voter
{
    public function __construct(
        private readonly ProjectContext $projectContext,
        private readonly PlanResolver $planResolver,
    ) {}

    protected function supports(string $attribute, mixed $subject): bool
    {
        return
            in_array($attribute, Plan::stringCases())
            && ($subject instanceof Project || $subject === null)
        ;
    }

    /**
     * @param ?Project $subject
     */
    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $subject ??= $this->projectContext->getCurrentProject();
        $plan = Plan::from($attribute);

        return $this->planResolver->resolve($subject)->score() >= $plan->score();
    }
}
