<?php

declare(strict_types=1);

namespace App\Security\Voter\Right;

use App\Entity\Enums\Plan;
use App\Entity\Project;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * @extends Voter<string, ?Project>
 */
class RightVoter extends Voter
{
    public function __construct(
        private readonly Security $security,
    ) {}

    protected function supports(string $attribute, mixed $subject): bool
    {
        return
            in_array($attribute, Right::stringCases())
            && ($subject instanceof Project || $subject === null)
        ;
    }

    /**
     * @param ?Project $subject
     */
    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $right = Right::from($attribute);

        return match ($right) {
            Right::AUTOREFRESH,
            Right::TIME_SERIES_CHART,
            Right::ALERTING => $this->security->isGranted(Plan::PRO->value, $subject),
        };
    }
}
