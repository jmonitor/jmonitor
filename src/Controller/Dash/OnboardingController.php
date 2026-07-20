<?php

declare(strict_types=1);

namespace App\Controller\Dash;

use App\Entity\Enums\UserStatus;
use App\Entity\Project;
use App\Entity\User;
use App\Form\Account\AccountNotificationsType;
use App\Form\Project\ProjectType;
use App\Project\ProjectCreator;
use App\Repository\ProjectInvitationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

class OnboardingController extends AbstractController
{
    #[Route('/onboarding', name: 'dashboard.onboarding')]
    public function dash(#[CurrentUser] User $user, Request $request, EntityManagerInterface $em, ProjectInvitationRepository $projectInvitationRepository, ProjectCreator $projectCreator): Response
    {
        // account
        $accountForm = $this->createForm(AccountNotificationsType::class, $user, [
            'action' => $this->generateUrl('dashboard.onboarding'),
        ]);

        $accountForm->handleRequest($request);

        if ($accountForm->isSubmitted() && $accountForm->isValid()) {
            // TODO clarify this status handling
            $user->setStatus(UserStatus::ACTIVE);

            $em->flush();

            $this->addFlash('success', 'Account settings saved');

            return $this->redirectToRoute('dashboard.onboarding');
        }

        // invitations
        $invitations = $projectInvitationRepository->findBy(['email' => $user->getEmail()]);

        // project
        $project = new Project();

        $projectForm = $this->createForm(ProjectType::class, $project, [
            'action' => $this->generateUrl('dashboard.onboarding'),
        ]);

        $projectForm->handleRequest($request);

        if ($projectForm->isSubmitted() && $projectForm->isValid()) {
            $user->setStatus(UserStatus::ACTIVE);

            $projectCreator->create($project, $user);

            return $this->render('dash/onboarding/_project_created_success.html.twig', [
                'project' => $project,
            ]);
        }

        return $this->render('dash/onboarding/onboarding.html.twig', [
            'accountForm' => $accountForm,
            'projectForm' => $projectForm,
            'invitations' => $invitations,
            'step2enabled' => $user->isSubscribedToJmonitorMarketing() !== null,
        ]);
    }

}
