<?php

declare(strict_types=1);

namespace App\Controller\Dash\Account;

use App\Entity\Project;
use App\Entity\User;
use App\Form\Account\AccountEmailType;
use App\Form\Account\AccountNotificationsType;
use App\Form\Account\ProjectUserAlertNotificationType;
use App\Repository\ProjectInvitationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsCsrfTokenValid;

class AccountController extends AbstractController
{
    #[Route('/account', name: 'account')]
    public function account(): Response
    {
        return $this->render('dash/account/account.html.twig');
    }

    #[Route('/account/email', name: 'account.email')]
    public function email(#[CurrentUser] User $user, Request $request, EntityManagerInterface $em): Response
    {
        if ($user->isDemo()) {
            return $this->render('dash/account/email.html.twig', [
                'form' => null,
            ]);
        }

        $form = $this->createForm(AccountEmailType::class, $user, [
            'action' => $this->generateUrl('account.email'),
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            $this->addFlash('success', 'E-mail saved');

            return $this->redirectToRoute('account');
        }

        return $this->render('dash/account/email.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/account/projects', name: 'account.projects')]
    public function projects(): Response
    {
        return $this->render('dash/account/projects.html.twig', [
        ]);
    }

    #[Route('/account/projects/leave/{uuid:project}', name: 'account.projects.leave', methods: ['POST'])]
    public function leaveProject(#[CurrentUser] User $user, Project $project, EntityManagerInterface $em): Response
    {
        if ($user->isDemo()) {
            throw $this->createAccessDeniedException('The demo account cannot leave the demo project.');
        }

        foreach ($user->getProjectUsers() as $projectUser) {
            if ($projectUser->getProject() === $project) {
                $user->removeProjectUser($projectUser);

                $em->flush();

                $this->addFlash('success', 'You have left the project');
                break;
            }
        }

        return $this->redirectToRoute('account.projects');
    }

    #[Route(path: '/account/project-notifications', name: 'account.project_notifications')]
    public function projectNotifications(#[CurrentUser] User $user, Request $request, EntityManagerInterface $em): Response
    {
        $builder = $this->createFormBuilder(['projectUsers' => $user->getProjectUsers()], [
            'action' => $this->generateUrl('account.project_notifications'),
        ]);

        $builder->add('projectUsers', CollectionType::class, [
            'label' => false,
            'entry_type' => ProjectUserAlertNotificationType::class,
            'entry_options' => ['label' => false],
        ]);

        $form = $builder->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            $this->addFlash('success', 'Settings saved');

            return $this->redirectToRoute('account.project_notifications');
        }

        return $this->render('dash/account/project-notifications.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route(path: '/account/account-notifications', name: 'account.account-notifications')]
    public function accountNotifications(#[CurrentUser] User $user, Request $request, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(AccountNotificationsType::class, $user, [
            'action' => $this->generateUrl('account.account-notifications'),
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            $this->addFlash('success', 'Settings saved');

            return $this->redirectToRoute('account.account-notifications');
        }

        return $this->render('dash/account/account-notifications.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route(path: '/account/delete', name: 'account.delete')]
    #[IsCsrfTokenValid('delete-account', methods: ['POST'])]
    public function removeAccount(#[CurrentUser] User $user, EntityManagerInterface $em, Request $request, Security $security): Response
    {
        if ($request->isMethod('POST')) {
            if (count($user->getProjectUsers()) > 0) {
                $this->addFlash('danger', 'You cannot delete your account while you have projects assigned to you.');

                return $this->redirectToRoute('account');
            }

            $user->setDeletedAt(new \DateTimeImmutable());
            $em->flush();

            $this->addFlash('success', 'Account deleted');

            return $security->logout(false);
        }

        return $this->render('dash/account/remove.html.twig');
    }

    #[Route('/account/invites', name: 'account.invites')]
    public function invites(#[CurrentUser] User $user, ProjectInvitationRepository $projectInvitationRepository): Response
    {
        return $this->render('dash/account/invites.html.twig', [
            'invitations' => $projectInvitationRepository->findBy(['email' => $user->getEmail()]),
        ]);
    }
}
