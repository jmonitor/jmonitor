<?php

declare(strict_types=1);

namespace App\Controller\Dash\Project\Settings;

use App\Entity\Enums\Plan;
use App\Entity\Project;
use App\Plan\PlanResolver;
use App\Plan\StripeSessionFactory;
use App\Security\Voter\ProjectVoter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/p/{uuid:project}/settings/plan', condition: "env('APP_EDITION') == 'cloud'")]
#[IsGranted(ProjectVoter::PROJECT_USER, subject: 'project')]
class SettingsPlanController extends AbstractController
{
    #[Route('', name: 'project.settings.plan')]
    public function plan(Project $project, PlanResolver $planResolver): Response
    {
        return $this->render('dash/project/settings/team/plan.html.twig', [
            'plans' => Plan::orderedCases(),
            'current_plan' => $planResolver->resolve($project),
            'stripe_portal_url' => $this->getParameter('stripe.portal_url'),
        ]);
    }

    #[Route('/subscribe/{plan}', name: 'project.settings.plan.subscribe')]
    #[IsGranted(ProjectVoter::PROJECT_ADMIN, subject: 'project')]
    public function subscribe(Project $project, Plan $plan, StripeSessionFactory $sessionFactory): Response
    {
        if (!$plan->isPurchasable()) {
            throw $this->createNotFoundException();
        }

        return $this->redirect($sessionFactory->createSession($plan));
    }

    #[Route('/payment_success', name: 'project.settings.plan.payment_success')]
    public function paiementSuccess(Project $project, EntityManagerInterface $em, ClockInterface $clock): Response
    {
        // The subscription is created by the Stripe webhook, which can lag behind this
        // redirect: give it up to 5s to land so the page reflects the new plan.
        $deadline = $clock->now()->modify('+5 seconds');

        while (!$project->getActiveSubscription()?->getStripeSubscriptionId() && $clock->now() < $deadline) {
            $clock->sleep(1);
            $em->refresh($project);

            // refresh() is shallow: reload the subscription too, in case the webhook
            // updated the existing row instead of linking a new one
            $subscription = $project->getSubscription();
            if ($subscription !== null) {
                $em->refresh($subscription);
            }
        }

        if ($project->getActiveSubscription()?->getStripeSubscriptionId()) {
            $this->addFlash('success', 'Payment successful, thank you! Your subscription is now active.');
        } else {
            $this->addFlash('success', 'Payment successful, thank you! Your subscription will be active in a few moments.');
        }

        return $this->redirectToRoute('project.settings.plan', ['uuid' => $project->getUuid()]);
    }
}
