<?php

declare(strict_types=1);

namespace App\Controller\Dash\Project;

use App\Entity\Enums\ProjectStatus;
use App\Entity\Enums\UserStatus;
use App\Entity\Project;
use App\Entity\User;
use App\Form\Project\ProjectType;
use App\Metrics\CollectorContext;
use App\Plan\Edition;
use App\Plan\PlanResolver;
use App\Project\ProjectContext;
use App\Project\ProjectCreator;
use App\Repository\AlertRepository;
use App\Security\Voter\ProjectVoter;
use App\Version\CollectorUpdateChecker;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class ProjectController extends AbstractController
{
    public function __construct(
        private readonly Edition $edition,
    ) {}

    #[Route('/new', name: 'project.create')]
    public function create(Request $request, #[CurrentUser] User $user, ProjectCreator $projectCreator): Response
    {
        if ($user->getStatus() === UserStatus::ONBOARDING) {
            return $this->redirectToRoute('dashboard.onboarding');
        }

        if ($user->isDemo()) {
            $this->addFlash('warning', 'The demo account cannot create projects.');

            return $this->redirectToRoute('dashboard');
        }

        $project = new Project();

        $form = $this->createForm(ProjectType::class, $project);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $projectCreator->create($project, $user);

            $this->addFlash('success', 'Project created successfully!');

            return $this->redirectToRoute('project.dashboard', ['uuid' => $project->getUuid()]);
        }

        return $this->render('dash/project/create.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/p/{uuid:project}', name: 'project.dashboard', priority: -1)]
    #[IsGranted(ProjectVoter::PROJECT_USER, subject: 'project')]
    public function dashboard(Project $project, AlertRepository $alertRepository, PlanResolver $planResolver): Response
    {
        if ($project->getStatus() === ProjectStatus::NEW) {
            return $this->render('dash/project/dashboard/dashboard_new_project.html.twig');
        }

        return $this->render('dash/project/dashboard/dashboard_' . $project->getStatus()->value . '_project.html.twig', [
            'project' => $project,
            'plan' => $planResolver->resolve($project),
            'subscription' => $project->getActiveSubscription(),
            'stripe_portal_url' => $this->edition->isCloud() ? $this->getParameter('stripe.portal_url') : null,
            'nb_alerts' => $alertRepository->count(['project' => $project]),
        ]);
    }

    /**
     * Loaded in its own request so the dashboard never waits on the outbound call.
     */
    #[Route('/p/{uuid:project}/_collector-update', name: 'project.collector.update')]
    #[IsGranted(ProjectVoter::PROJECT_USER, subject: 'project')]
    public function collectorUpdate(Project $project, ProjectContext $projectContext, CollectorContext $collectorContext, CollectorUpdateChecker $updateChecker): Response
    {
        // The project is taken from the URL rather than guessed from the session:
        // two tabs open on two projects would otherwise share one answer.
        $projectContext->setCurrentProject($project);

        return $this->render('dash/project/_collector_update.html.twig', [
            'status' => $updateChecker->check($collectorContext->getCollectorVersion()),
        ]);
    }
}
