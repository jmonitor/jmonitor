<?php

declare(strict_types=1);

namespace App\Plan;

use App\Entity\Enums\Plan;
use App\Entity\Order;
use App\Entity\User;
use App\Project\ProjectContext;
use Doctrine\ORM\EntityManagerInterface;
use Stripe\StripeClient;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Returns the Stripe checkout page URLs for the JMonitor plans.
 * https://docs.stripe.com/api/checkout/sessions/create
 */
readonly class StripeSessionFactory
{
    /**
     * Trials granted by JMonitor last 7 days; this cap only bites on the long-lived
     * subscriptions provisioned in dev/demo, which Stripe rejects (its own limit is 730 days).
     */
    private const int MAX_TRIAL_DAYS = 14;

    public function __construct(
        #[Autowire(env: 'STRIPE_PRO_MONTHLY_PRICE_ID')]
        private string $proMonthlyPriceId,
        #[Autowire(env: 'STRIPE_MAX_MONTHLY_PRICE_ID')]
        private string $maxMonthlyPriceId,
        private StripeClient $stripe,
        private Security $security,
        private UrlGeneratorInterface $urlGenerator,
        private ProjectContext $projectContext,
        private EntityManagerInterface $em,
    ) {}

    public function createSession(Plan $plan): string
    {
        if (!$plan->isPurchasable()) {
            throw new \InvalidArgumentException(sprintf('Plan "%s" cannot be purchased', $plan->value));
        }

        $project = $this->projectContext->getCurrentProject();
        $params = $this->getDefaultSessionParams();

        $params = array_merge($params, [
            'line_items' => [
                [
                    'price' => match ($plan) {
                        Plan::PRO => $this->proMonthlyPriceId,
                        Plan::MAX => $this->maxMonthlyPriceId,
                        default => throw new \InvalidArgumentException(sprintf('No price configured for plan "%s"', $plan->value)),
                    },
                    'quantity' => 1,
                ],
            ],
            'metadata' => [
                'project_id' => $project->getId(),
                'plan' => $plan->value,
                'frequency' => 'monthly',
            ],
            'success_url' => $this->urlGenerator->generate('project.settings.plan.payment_success', ['uuid' => $project->getUuid()], UrlGeneratorInterface::ABSOLUTE_URL),
            'cancel_url' => $this->urlGenerator->generate('project.settings.plan', ['uuid' => $project->getUuid()], UrlGeneratorInterface::ABSOLUTE_URL),
        ]);

        // Ongoing trial (non-Stripe subscription): the remaining days are credited (capped),
        // billing starts at the end of the trial. Stripe requires trial_end >= 48h in the future.
        $activeSubscription = $project->getActiveSubscription();

        if ($activeSubscription && !$activeSubscription->getStripeSubscriptionId()) {
            $now = time();
            $trialEnd = min($activeSubscription->getEndAt()->getTimestamp(), $now + self::MAX_TRIAL_DAYS * 86400);

            if ($trialEnd >= $now + 48 * 3600) {
                $params['subscription_data']['trial_end'] = $trialEnd;
            }
        }

        // @phpstan-ignore-next-line
        return $this->stripe->checkout->sessions->create($params)->url;
    }

    private function getUser(): User
    {
        $user = $this->security->getUser();

        if ($user instanceof User) {
            return $user;
        }

        throw new \LogicException('User should be authenticated');
    }

    /**
     * @return mixed[] the common params for the checkout sessions.
     * At least these must be added:
     * - 'line_items'
     * - 'success_url'
     * and preferably
     * - 'cancel_url'
     * - 'metadata'
     */
    private function getDefaultSessionParams(): array
    {
        $user = $this->getUser();
        $lastOrder = $this->em->getRepository(Order::class)->findOneBy(['project' => $this->projectContext->getCurrentProject()], ['createdAt' => 'DESC']);

        return array_filter([
            'automatic_tax' => ['enabled' => true],
            'tax_id_collection' => [
                'enabled' => true,
                'required' => 'never',
            ],
            'client_reference_id' => $user->getId(),
            'customer' => $lastOrder?->getStripeCustomerId(),
            'customer_email' => $lastOrder?->getStripeCustomerId() ? null : $user->getEmail(),
            'mode' => 'subscription',
            'subscription_data' => [
                'metadata' => [
                    'project_id' => $this->projectContext->getCurrentProject()->getId(),
                ],
            ],
            'ui_mode' => 'hosted',
            'billing_address_collection' => 'required',
            // 'invoice_creation' => ['enabled' => true] is automatic in subscription mode
        ]);
    }
}
