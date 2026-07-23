<?php

declare(strict_types=1);

namespace App\Tests\EventListener;

use App\EventListener\FrameAncestorsListener;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class FrameAncestorsListenerTest extends TestCase
{
    public function testDeniesFramingByDefault(): void
    {
        $response = $this->dispatch('project.metrics.component');

        $this->assertSame("frame-ancestors 'self'", $response->headers->get('Content-Security-Policy'));
    }

    public function testAllowsFramingPublicEmbeds(): void
    {
        $response = $this->dispatch('embed.public');

        $this->assertFalse($response->headers->has('Content-Security-Policy'));
    }

    public function testDoesNotOverrideAnExistingPolicy(): void
    {
        $response = new Response();
        $response->headers->set('Content-Security-Policy', "default-src 'self'");

        $response = $this->dispatch('project.metrics.component', $response);

        $this->assertSame("default-src 'self'", $response->headers->get('Content-Security-Policy'));
    }

    private function dispatch(string $route, ?Response $response = null): Response
    {
        $request = new Request(attributes: ['_route' => $route]);
        $response ??= new Response();

        $event = new ResponseEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            $response,
        );

        new FrameAncestorsListener()->onResponse($event);

        return $event->getResponse();
    }
}
