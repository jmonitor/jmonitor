<?php

declare(strict_types=1);

namespace App\Controller\Dash\Account;

use App\Entity\User;
use App\Form\Account\UserPasswordType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsCsrfTokenValid;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class AccountAuthController extends AbstractController
{
    #[Route('/account/authentication/', name: 'account.authentication')]
    public function authentication(): Response
    {
        return $this->render('dash/account/authentication/authentication.html.twig');
    }

    #[Route('/account/authentication/create_password', name: 'account.authentication.password')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function authenticationFormPassword(#[CurrentUser] User $user, Request $request, UserPasswordHasherInterface $userPasswordHasher, EntityManagerInterface $em): Response
    {
        if ($user->isDemo()) {
            throw $this->createAccessDeniedException('The demo account password cannot be changed.');
        }

        $form = $this->createForm(UserPasswordType::class, options: [
            'action' => $this->generateUrl('account.authentication.password'),
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = $form->get('plainPassword')->getData();

            $user->setPassword($userPasswordHasher->hashPassword($user, $plainPassword));

            $em->flush();

            $this->addFlash('success', 'Password updated');

            return $this->redirectToRoute('account');
        }

        return $this->render('dash/account/authentication/create_password.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/account/authentication/remove_password', name: 'account.authentication.remove_password')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    #[IsCsrfTokenValid('delete-password')]
    public function authenticationRemovePassword(#[CurrentUser] User $user, EntityManagerInterface $em): Response
    {
        if ($user->isDemo()) {
            throw $this->createAccessDeniedException('The demo account password cannot be removed.');
        }

        $user->setPassword(null);
        $em->flush();

        $this->addFlash('success', 'Password removed');

        return $this->redirectToRoute('account');
    }
}
