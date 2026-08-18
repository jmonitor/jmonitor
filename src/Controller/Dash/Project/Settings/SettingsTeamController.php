<?php

declare(strict_types=1);

namespace App\Controller\Dash\Project\Settings;

use App\Entity\Enums\ProjectRole;
use App\Entity\Project;
use App\Entity\ProjectInvitation;
use App\Entity\User;
use App\Form\Project\ProjectInvitationType;
use App\Security\Voter\ProjectVoter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsCsrfTokenValid;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/p/{uuid:project}/settings/team')]
#[IsGranted(ProjectVoter::PROJECT_USER, subject: 'project')]
class SettingsTeamController extends AbstractController
{
    #[Route('', name: 'project.settings.team')]
    public function team(Project $project): Response
    {
        return $this->render('dash/project/settings/team/team.html.twig', [
            'project' => $project,
        ]);
    }

    #[Route('/invite', name: 'project.settings.team.invite')]
    #[IsGranted(ProjectVoter::PROJECT_ADMIN, subject: 'project')]
    public function invite(Project $project, Request $request, EntityManagerInterface $em, MailerInterface $mailer): Response
    {
        $invitation = new ProjectInvitation();
        $invitation->setProject($project);
        $invitation->setRole(ProjectRole::VIEWER);

        $form = $this->createForm(ProjectInvitationType::class, $invitation, [
            'action' => $this->generateUrl('project.settings.team.invite', ['uuid' => $project->getUuid()]),
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($invitation);
            $em->flush();

            $email = new Email();
            $email->subject('Invitation to join project ' . $project->getName());
            $email->to($invitation->getEmail());
            $email->html($this->renderView('email/project/invitation.html.twig', [
                'invitation' => $invitation,
            ]));
            $mailer->send($email);

            $this->addFlash('success', 'Invitation sent.');

            return $this->redirectToRoute('project.settings.team', [
                'uuid' => $project->getUuid(),
            ]);
        }

        return $this->render('dash/project/settings/team/invite.html.twig', [
            'project' => $project,
            'form' => $form,
        ]);
    }

    #[Route('/invite/{invitation:invitation.uniquid}/cancel', name: 'project.settings.team.invite.cancel', methods: ['POST'])]
    #[IsGranted(ProjectVoter::PROJECT_ADMIN, subject: 'project')]
    #[IsCsrfTokenValid('cancel_invitation')]
    public function cancelInvitation(Project $project, ProjectInvitation $invitation, EntityManagerInterface $em, #[CurrentUser] User $user): Response
    {
        if ($invitation->getProject() !== $project) {
            throw $this->createNotFoundException();
        }

        if (!$user->getRoleInProject($project)->canManage($invitation->getRole())) {
            throw $this->createAccessDeniedException();
        }

        $em->remove($invitation);
        $em->flush();

        $this->addFlash('success', 'Invitation canceled.');

        return $this->redirectToRoute('project.settings.team', [
            'uuid' => $project->getUuid(),
        ]);
    }

    #[Route('/members/{user:user.uuid}/remove', name: 'project.settings.team.member.remove', methods: ['POST'])]
    #[IsGranted(ProjectVoter::PROJECT_ADMIN, subject: 'project')]
    #[IsCsrfTokenValid('remove_member')]
    public function removeMember(Project $project, User $user, EntityManagerInterface $em, #[CurrentUser] User $currentUser): Response
    {
        $projectUser = $project->getProjectUserByUser($user);

        if (!$projectUser) {
            throw $this->createNotFoundException();
        }

        if (!$currentUser->getRoleInProject($project)->canManage($projectUser->getRole())) {
            throw $this->createAccessDeniedException();
        }

        $em->remove($projectUser);
        $em->flush();

        $this->addFlash('success', 'Member removed.');

        return $this->redirectToRoute('project.settings.team', [
            'uuid' => $project->getUuid(),
        ]);
    }

    #[Route('/members/{user:user.uuid}/set_role/{role}', name: 'project.settings.team.member.set_role', methods: ['POST'])]
    #[IsGranted(ProjectVoter::PROJECT_ADMIN, subject: 'project')]
    #[IsCsrfTokenValid('set_role')]
    public function setRole(Project $project, User $user, EntityManagerInterface $em, #[CurrentUser] User $currentUser, ProjectRole $role): Response
    {
        $projectUser = $project->getProjectUserByUser($user);

        if (!$projectUser) {
            throw $this->createNotFoundException();
        }

        $currentUserRole = $currentUser->getRoleInProject($project);

        // The actor must be able to manage both the member's current role and the role being assigned,
        // otherwise an admin could e.g. promote a viewer straight to owner.
        if (!$currentUserRole->canManage($projectUser->getRole()) || !$currentUserRole->canManage($role)) {
            throw $this->createAccessDeniedException();
        }

        $projectUser->setRole($role);

        $em->flush();

        $this->addFlash('success', 'Role updated.');

        return $this->redirectToRoute('project.settings.team', [
            'uuid' => $project->getUuid(),
        ]);
    }
}
