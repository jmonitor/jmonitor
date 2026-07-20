<?php

declare(strict_types=1);

namespace App\Collector;

use App\Entity\Enums\Plan;
use App\Entity\Project;
use App\Plan\PlanResolver;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;

readonly class CollectorRateLimiterProvider
{
    public function __construct(
        #[Target('collector.free')]
        private RateLimiterFactoryInterface $freeRateLimiterFactory,
        #[Target('collector.pro')]
        private RateLimiterFactoryInterface $proRateLimiterFactory,
        #[Target('collector.max')]
        private RateLimiterFactoryInterface $maxRateLimiterFactory,
        #[Target('collector.selfhosted')]
        private RateLimiterFactoryInterface $selfHostedRateLimiterFactory,
        private PlanResolver $planResolver,
    ) {}

    public function getRateLimiterFactory(Project $project): RateLimiterFactoryInterface
    {
        return match ($this->planResolver->resolve($project)) {
            Plan::FREE => $this->freeRateLimiterFactory,
            Plan::PRO => $this->proRateLimiterFactory,
            Plan::MAX => $this->maxRateLimiterFactory,
            Plan::SELF_HOSTED => $this->selfHostedRateLimiterFactory,
        };
    }
}
