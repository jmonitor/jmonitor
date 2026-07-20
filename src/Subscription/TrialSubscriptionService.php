<?php

declare(strict_types=1);

namespace App\Subscription;

use App\Entity\Enums\Plan;
use App\Entity\Project;
use App\Entity\Subscription;
use Doctrine\ORM\EntityManagerInterface;

final readonly class TrialSubscriptionService
{
    public function __construct(
        private EntityManagerInterface $em,
    ) {}

    public function grantTrial(Project $project): void
    {
        $subscription = new Subscription();
        $subscription->setPlan(Plan::PRO);
        $subscription->setEndAt(new \DateTimeImmutable('+7 days'));
        $subscription->setAutoRenew(false);
        $subscription->setProject($project);

        $this->em->persist($subscription);
    }
}
