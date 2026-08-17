<?php

declare(strict_types=1);

namespace App\Tests\EventListener\ExceptionListener;

use App\Entity\Enums\ProjectRole;
use App\Entity\Project;
use App\Entity\ProjectUser;
use App\Entity\User;
use App\EventListener\ExceptionListener\AccessDeniedListener;
use App\Project\ProjectContext;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class AccessDeniedListenerTest extends TestCase
{
    private const string DOMAIN = 'jmonitor.test';
    private const string DASHBOARD_URL = 'http://dash.jmonitor.test/';

    public function testAdminHostSendsANonAdminToTheDashboard(): void
    {
        $event = $this->denialEvent('http://admin.jmonitor.test/');

        $this->makeListener()->onNavigationAccessDenied($event);

        $this->assertRedirectsTo(self::DASHBOARD_URL, $event);
        $this->assertNotEmpty($this->flashes($event));
    }

    /**
     * A referer on the admin host is denied in turn — following it loops the browser.
     */
    public function testAdminHostIgnoresTheReferer(): void
    {
        $event = $this->denialEvent('http://admin.jmonitor.test/?crudAction=index', referer: 'http://admin.jmonitor.test/');

        $this->makeListener()->onNavigationAccessDenied($event);

        $this->assertRedirectsTo(self::DASHBOARD_URL, $event);
    }

    public function testAdminHostSendsANonMemberToTheDashboardToo(): void
    {
        $event = $this->denialEvent('http://admin.jmonitor.test/');

        $this->makeListener(member: false)->onNavigationAccessDenied($event);

        $this->assertRedirectsTo(self::DASHBOARD_URL, $event);
    }

    public function testAdminHostDenialOfAnAdminIsLeftAlone(): void
    {
        $event = $this->denialEvent('http://admin.jmonitor.test/');

        $this->makeListener(member: false, admin: true)->onNavigationAccessDenied($event);

        $this->assertNull($event->getResponse());
    }

    public function testMemberIsSentBackToTheReferer(): void
    {
        $event = $this->denialEvent('http://dash.jmonitor.test/p/uuid/settings', referer: 'http://dash.jmonitor.test/p/uuid');

        $this->makeListener()->onNavigationAccessDenied($event);

        $this->assertRedirectsTo('http://dash.jmonitor.test/p/uuid', $event);
    }

    public function testOffsiteRefererIsIgnored(): void
    {
        $event = $this->denialEvent('http://dash.jmonitor.test/p/uuid/settings', referer: 'http://evil.example/landing');

        $this->makeListener()->onNavigationAccessDenied($event);

        $this->assertRedirectsTo('/', $event);
    }

    public function testTurboFrameRequestIsLeftToTheOtherHandler(): void
    {
        $event = $this->denialEvent('http://admin.jmonitor.test/', headers: ['Turbo-Frame' => 'x']);

        $this->makeListener()->onNavigationAccessDenied($event);

        $this->assertNull($event->getResponse());
    }

    private function makeListener(bool $member = true, bool $admin = false): AccessDeniedListener
    {
        $project = new Project();
        $user = new User();

        if ($member) {
            $user->addProjectUser(new ProjectUser()->setProject($project)->setRole(ProjectRole::VIEWER));
        }

        $security = $this->createStub(Security::class);
        $security->method('getUser')->willReturn($user);
        $security->method('isGranted')->willReturn($admin);

        $projectContext = $this->createStub(ProjectContext::class);
        $projectContext->method('getCurrentProject')->willReturn($project);

        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $urlGenerator->method('generate')
            ->with('dashboard', [], UrlGeneratorInterface::ABSOLUTE_URL)
            ->willReturn(self::DASHBOARD_URL);

        return new AccessDeniedListener($security, $projectContext, $urlGenerator, self::DOMAIN);
    }

    /**
     * @param array<string, string> $headers
     */
    private function denialEvent(string $uri, ?string $referer = null, array $headers = []): ExceptionEvent
    {
        if ($referer !== null) {
            $headers['Referer'] = $referer;
        }

        $request = Request::create($uri);
        $request->headers->add($headers);
        $request->setSession(new Session(new MockArraySessionStorage()));

        return new ExceptionEvent(
            $this->createStub(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            new AccessDeniedHttpException(),
        );
    }

    private function assertRedirectsTo(string $url, ExceptionEvent $event): void
    {
        $response = $event->getResponse();

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame($url, $response->getTargetUrl());
    }

    /**
     * @return list<string>
     */
    private function flashes(ExceptionEvent $event): array
    {
        /** @var Session $session */
        $session = $event->getRequest()->getSession();

        return $session->getFlashBag()->peek('danger');
    }
}
