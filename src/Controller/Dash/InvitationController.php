<?php

declare(strict_types=1);

namespace App\Controller\Dash;

use App\Entity\Enums\UserStatus;
use App\Entity\ProjectInvitation;
use App\Entity\ProjectUser;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

/**
 * The same routes are used to accept an invitation from the account or from the onboarding.
 */
class InvitationController extends AbstractController
{
    #[Route('/invitations/{uniquid:invitation}/accept', name: 'invitation.accept')]
    public function dash(ProjectInvitation $invitation, #[CurrentUser] User $user, EntityManagerInterface $em): Response
    {
        if ($invitation->getEmail() !== $user->getEmail()) {
            throw $this->createNotFoundException();
        }

        $projectUser = new ProjectUser();
        $projectUser->setUser($user);
        $projectUser->setProject($invitation->getProject());
        $projectUser->setRole($invitation->getRole());

        $em->persist($projectUser);
        $em->remove($invitation);
        $user->setStatus(UserStatus::ACTIVE);

        $em->flush();

        $this->addFlash('success', 'Invitation accepted.');

        return $this->redirectToRoute('project.dashboard', ['uuid' => $projectUser->getProject()->getUuid()]);
    }

    #[Route('/invitations/{uniquid:invitation}/refuse', name: 'invitation.refuse', methods: ['POST'])]
    public function refuse(ProjectInvitation $invitation, #[CurrentUser] User $user, EntityManagerInterface $em, Request $request): Response
    {
        if ($invitation->getEmail() !== $user->getEmail()) {
            throw $this->createNotFoundException();
        }

        $em->remove($invitation);
        $em->flush();

        $this->addFlash('success', 'Invitation refused.');

        return $this->redirect($request->headers->get('referer') ?? $this->generateUrl('dashboard'));
    }
}
