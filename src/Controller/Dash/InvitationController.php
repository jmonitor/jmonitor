<?php

declare(strict_types=1);

namespace App\Controller\Dash;

use App\Entity\ProjectInvitation;
use App\Entity\User;
use App\Project\InvitationAccepter;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsCsrfTokenValid;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

/**
 * The same accept/refuse routes serve the account page, the onboarding and the
 * confirmation page reached through an invitation link (join.html.twig).
 */
class InvitationController extends AbstractController
{
    use TargetPathTrait;

    /**
     * Public entry point of an invitation: the only invitation URL that leaves the
     * application (e-mail, link copied by an admin). Routes the visitor depending on
     * whether they are logged in and whether the invited address already has an account.
     */
    #[Route('/join/{uniquid:invitation}', name: 'invitation.join', methods: ['GET'])]
    public function join(ProjectInvitation $invitation, Request $request, Security $security, UserRepository $userRepository): Response
    {
        $user = $security->getUser();

        if ($user instanceof User) {
            if ($user->getEmail() !== $invitation->getEmail()) {
                $this->addFlash('warning', sprintf('This invitation was sent to %s. Log in with that address to accept it.', $invitation->getEmail()));

                return $this->redirectToRoute('dashboard');
            }

            return $this->render('dash/invitation/join.html.twig', [
                'invitation' => $invitation,
            ]);
        }

        if ($userRepository->findOneBy(['email' => $invitation->getEmail()])) {
            $this->saveTargetPath($request->getSession(), 'main', $this->generateUrl('invitation.join', ['uniquid' => $invitation->getUniquid()]));

            return $this->redirectToRoute('security.login');
        }

        return $this->redirectToRoute('security.register.invitation', ['uniquid' => $invitation->getUniquid()]);
    }

    #[Route('/invitations/{uniquid:invitation}/accept', name: 'invitation.accept', methods: ['POST'])]
    #[IsCsrfTokenValid('accept_invitation')]
    public function dash(ProjectInvitation $invitation, #[CurrentUser] User $user, InvitationAccepter $invitationAccepter): Response
    {
        if ($invitation->getEmail() !== $user->getEmail()) {
            throw $this->createNotFoundException();
        }

        $projectUser = $invitationAccepter->accept($invitation, $user);

        $this->addFlash('success', 'Invitation accepted.');

        return $this->redirectToRoute('project.dashboard', ['uuid' => $projectUser->getProject()->getUuid()]);
    }

    #[Route('/invitations/{uniquid:invitation}/refuse', name: 'invitation.refuse', methods: ['POST'])]
    #[IsCsrfTokenValid('refuse_invitation')]
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
