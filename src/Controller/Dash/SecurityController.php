<?php

declare(strict_types=1);

namespace App\Controller\Dash;

use App\Entity\ProjectInvitation;
use App\Entity\User;
use App\Form\Security\ChangePasswordFormType;
use App\Form\Security\PasswordLostType;
use App\Form\Security\RegisterType;
use App\Project\InvitationAccepter;
use App\Repository\UserRepository;
use App\Security\Authenticator\GoogleAuthenticator;
use App\Security\Registration\RegistrationGate;
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class SecurityController extends AbstractController
{
    use TargetPathTrait;

    #[Route('/register', name: 'security.register')]
    public function register(Request $request, UserPasswordHasherInterface $userPasswordHasher, EntityManagerInterface $em, Security $security, RateLimiterFactoryInterface $registerFormLimiter, RegistrationGate $registrationGate): Response
    {
        if ($this->getUser()) {
            return $this->redirect('/');
        }

        // Invite-only instances keep /register/{uniquid} open: it is the only way in.
        if (!$registrationGate->isOpen()) {
            $this->addFlash('warning', 'Registration is by invitation only.');

            return $this->redirectToRoute('security.login');
        }

        return $this->handleRegistration(null, $request, $userPasswordHasher, $em, $security, $registerFormLimiter, null);
    }

    /**
     * Registration through an invitation link: the address is locked to the invited one
     * and the invitation is accepted on submit.
     */
    #[Route('/register/{uniquid:invitation}', name: 'security.register.invitation')]
    public function registerWithInvitation(ProjectInvitation $invitation, Request $request, UserPasswordHasherInterface $userPasswordHasher, EntityManagerInterface $em, Security $security, RateLimiterFactoryInterface $registerFormLimiter, UserRepository $userRepository, InvitationAccepter $invitationAccepter): Response
    {
        // Already logged in, or the invited address already has an account: the join
        // dispatcher knows where to send them.
        if ($this->getUser() || $userRepository->findOneBy(['email' => $invitation->getEmail()])) {
            return $this->redirectToRoute('invitation.join', ['uniquid' => $invitation->getUniquid()]);
        }

        return $this->handleRegistration($invitation, $request, $userPasswordHasher, $em, $security, $registerFormLimiter, $invitationAccepter);
    }

    private function handleRegistration(?ProjectInvitation $invitation, Request $request, UserPasswordHasherInterface $userPasswordHasher, EntityManagerInterface $em, Security $security, RateLimiterFactoryInterface $registerFormLimiter, ?InvitationAccepter $invitationAccepter): Response
    {
        $user = new User();

        if ($invitation) {
            $user->setEmail($invitation->getEmail());
        }

        $form = $this->createForm(RegisterType::class, $user, [
            'action' => $invitation
                ? $this->generateUrl('security.register.invitation', ['uniquid' => $invitation->getUniquid()])
                : $this->generateUrl('security.register'),
            'invitation' => $invitation,
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $limiter = $registerFormLimiter->create($request->getClientIp());
            $limit = $limiter->consume();

            if (!$limit->isAccepted()) {
                $this->addFlash('danger', 'Too many registrations from your IP. Please try again in a minute.');

                return $this->redirectToRoute('security.register');
            }

            $user->setPassword($userPasswordHasher->hashPassword($user, $form->get('plainPassword')->getData()));

            $em->persist($user);

            if ($invitation && $invitationAccepter) {
                // Filling in a form that names the project is the consent, so there is no
                // extra confirmation step. The status must reach ACTIVE before login, or
                // LoginSuccessListenerForOnboarding overrides the response and sends the
                // invitee to /onboarding instead of the project.
                $projectUser = $invitationAccepter->accept($invitation, $user);

                $this->saveTargetPath($request->getSession(), 'main', $this->generateUrl('project.dashboard', ['uuid' => $projectUser->getProject()->getUuid()]));
            } else {
                $em->flush();
            }

            return $security->login($user, GoogleAuthenticator::class, 'main');
        }

        return $this->render('dash/security/register.html.twig', [
            'form' => $form,
            'invitation' => $invitation,
        ]);
    }

    #[Route('/login', name: 'security.login')]
    public function login(AuthenticationUtils $authenticationUtils, Request $request): Response
    {
        // Coming from the landing site (jmonitor.io) while already authenticated (incl. remember-me):
        // skip the login form and go straight to the dashboard.
        $refererHost = parse_url((string) $request->headers->get('referer'), PHP_URL_HOST);
        if ($refererHost === 'jmonitor.io' && $this->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            return $this->redirect('/');
        }

        // target_route in the query string: used where the code wants to redirect to a specific page after a relogin
        if ($request->query->get('target_route')) {
            $targetPath = match ($request->query->get('target_route')) {
                'account' => $this->generateUrl('account'),
                'apikey' => $this->generateUrl('project.settings.api_key', ['uuid' => $request->query->get('p')]),
                'project.dashboard' => $this->generateUrl('project.dashboard', ['uuid' => $request->query->get('p')]),
                default => null,
            };

            if ($targetPath) {
                $this->saveTargetPath($request->getSession(), 'main', $targetPath);
            }

            return $this->redirectToRoute('security.login');
        }

        if ($this->isGranted('IS_AUTHENTICATED_FULLY')) {
            $target = $this->getTargetPath($request->getSession(), 'main') ?? '/';

            $this->removeTargetPath($request->getSession(), 'main');

            return $this->redirect($target);
        }

        return $this->render('dash/security/login.html.twig', [
            'last_username' => $authenticationUtils->getLastUsername(),
            'error' => $authenticationUtils->getLastAuthenticationError(),
        ]);
    }

    #[Route(path: '/login/oauth', name: 'security.login.oauth', condition: "env('APP_EDITION') == 'cloud'")]
    public function googleLogin(ClientRegistry $clientRegistry): RedirectResponse
    {
        return $clientRegistry->getClient('google')->redirect(['openid', 'email', 'profile'], [
            'type' => 'google',
        ]);
    }

    #[Route('/password-lost', name: 'security.password_lost')]
    public function passwordLost(Request $request, UserRepository $userRepository, EntityManagerInterface $em, MailerInterface $mailer, RateLimiterFactoryInterface $passwordLostFormLimiter): Response
    {
        if ($this->getUser()) {
            return $this->redirect('/');
        }

        $form = $this->createForm(PasswordLostType::class, options: [
            'action' => $this->generateUrl('security.password_lost'),
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $limiter = $passwordLostFormLimiter->create($form->get('email')->getData());
            $limit = $limiter->consume();

            if (!$limit->isAccepted()) {
                $this->addFlash('danger', 'Too many password reset requests. Please try again in a minute.');

                return $this->redirectToRoute('security.password_lost');
            }

            $user = $userRepository->findOneBy([
                'email' => $form->get('email')->getData(),
            ]);

            if (!$user->getPasswordLostHash()) {
                $user->setPasswordLostHash(uniqid());
                $em->flush();
            }

            $email = new Email()
                ->to($user->getEmail())
                ->subject('Password reset')
                ->html($this->renderView('email/security/password_lost.html.twig', [
                    'passwordLostHash' => $user->getPasswordLostHash(),
                ]))
            ;

            $mailer->send($email);

            return $this->redirectToRoute('security.password_lost', ['check_email' => true]);
        }

        return $this->render('dash/security/password-lost.html.twig', [
            'form' => $form,
            'checkEmail' => $request->query->has('check_email'),
        ]);
    }

    #[Route('/password-retrieve/{passwordLostHash:user}', name: 'security.password_retrieve')]
    public function passwordRetrieve(Security $security, Request $request, UserPasswordHasherInterface $passwordHasher, EntityManagerInterface $em, ?User $user = null): Response
    {
        if (!$user) {
            $this->addFlash('warning', 'This password reset link is invalid or has expired.');

            return $this->redirect('/');
        }

        $form = $this->createForm(ChangePasswordFormType::class, options: [
            'action' => $this->generateUrl('security.password_retrieve', ['passwordLostHash' => $user->getPasswordLostHash()]),
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $encodedPassword = $passwordHasher->hashPassword(
                $user,
                $form->get('plainPassword')->getData(),
            );

            $user->setPassword($encodedPassword);
            $user->setPasswordLostHash(null);
            $em->flush();

            $this->addFlash('success', 'Password updated.');

            return $security->login($user, GoogleAuthenticator::class);
        }

        return $this->render('dash/security/password-reset.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/login/oauth/check', name: 'security.login.oauth.check', condition: "env('APP_EDITION') == 'cloud'")]
    public function oauthCheck(): Response
    {
        // Google seems to ping this URL sometimes when configuring OAuth.
        // Without a "state" query parameter the authenticator won't pick up the request,
        // so this controller is reachable.
        return new Response('', Response::HTTP_NO_CONTENT);
    }

    #[Route('/login/check', name: 'security.login.check', methods: ['POST'])]
    public function check(): void
    {
        throw new \Exception('Should not be reached');
    }

    #[Route('/logout', name: 'security.logout')]
    public function logout(): void
    {
        throw new \Exception('Should not be reached');
    }
}
