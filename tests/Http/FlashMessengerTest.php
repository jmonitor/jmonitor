<?php

declare(strict_types=1);

namespace App\Tests\Http;

use App\Http\FlashMessenger;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class FlashMessengerTest extends TestCase
{
    public function testATurboFrameWithoutSessionCookieDoesNotStartTheSession(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $request = $this->turboFrameRequest($session);

        $this->respond($request);

        $this->assertFalse($session->isStarted());
    }

    public function testFlashesOfAPreviousSessionAreSentAsToasts(): void
    {
        $storage = new MockArraySessionStorage();
        $storage->setSessionData(['_symfony_flashes' => ['success' => ['Saved']]]);
        $session = new Session($storage);
        $request = $this->turboFrameRequest($session);
        $request->cookies->set($session->getName(), 'id');

        $response = $this->respond($request);

        $this->assertSame('{"success":["Saved"]}', $response->headers->get('X-toasts'));
    }

    public function testFlashesAddedDuringTheRequestAreSentAsToasts(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $request = $this->turboFrameRequest($session);
        $session->getFlashBag()->add('success', 'Saved');

        $response = $this->respond($request);

        $this->assertSame('{"success":["Saved"]}', $response->headers->get('X-toasts'));
    }

    private function turboFrameRequest(Session $session): Request
    {
        $request = Request::create('/e/token/content');
        $request->headers->set('turbo-frame', 'embed');
        $request->setSession($session);

        return $request;
    }

    private function respond(Request $request): Response
    {
        $requestStack = new RequestStack();
        $requestStack->push($request);

        $event = new ResponseEvent($this->createStub(HttpKernelInterface::class), $request, HttpKernelInterface::MAIN_REQUEST, new Response());
        new FlashMessenger($requestStack, $this->createStub(Security::class))->onResponse($event);

        return $event->getResponse();
    }
}
