<?php

declare(strict_types=1);

namespace App\Tests\Bridge\Mercure;

use App\Bridge\Mercure\SameOriginHub;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Mercure\Hub;
use Symfony\Component\Mercure\Jwt\LcobucciFactory;
use Symfony\Component\Mercure\Jwt\StaticTokenProvider;
use Symfony\Component\Mercure\RemoteHubInterface;
use Symfony\Component\Mercure\Update;

class SameOriginHubTest extends TestCase
{
    private const INTERNAL_URL = 'http://app/.well-known/mercure';

    public function testDerivesThePublicUrlFromThePlainHttpRequestOrigin(): void
    {
        $hub = new SameOriginHub($this->innerHub(self::INTERNAL_URL), $this->requestStack(
            Request::create('http://dash.jmonitor.localhost:8080/dash/projects'),
        ));

        $this->assertSame('http://dash.jmonitor.localhost:8080/.well-known/mercure', $hub->getPublicUrl());
    }

    public function testDerivesThePublicUrlFromTheHttpsRequestOrigin(): void
    {
        $hub = new SameOriginHub($this->innerHub(self::INTERNAL_URL), $this->requestStack(
            Request::create('https://dash.jmonitor.io/dash/projects'),
        ));

        $this->assertSame('https://dash.jmonitor.io/.well-known/mercure', $hub->getPublicUrl());
    }

    public function testFallsBackToTheDecoratedHubOutsideOfARequest(): void
    {
        // Console context: messenger worker, jmonitor:collect.
        $hub = new SameOriginHub($this->innerHub(self::INTERNAL_URL), new RequestStack());

        $this->assertSame(self::INTERNAL_URL, $hub->getPublicUrl());
    }

    public function testKeepsAnEmptyPublicUrlWhenTheHubIsNotConfigured(): void
    {
        // AutoRefreshListener reads the empty value as "no hub, skip publishing".
        $hub = new SameOriginHub($this->innerHub(''), $this->requestStack(
            Request::create('https://dash.jmonitor.io/dash/projects'),
        ));

        $this->assertSame('', $hub->getPublicUrl());
    }

    public function testDelegatesTheInternalUrlAndTokenFactory(): void
    {
        $inner = $this->innerHub(self::INTERNAL_URL);
        $hub = new SameOriginHub($inner, new RequestStack());

        $this->assertSame(self::INTERNAL_URL, $hub->getUrl());
        $this->assertSame($inner->getFactory(), $hub->getFactory());
    }

    public function testDelegatesPublishing(): void
    {
        $update = new Update('https://jmonitor.io/metrics/consumed/1', '{}');

        $inner = $this->createMock(RemoteHubInterface::class);
        $inner->expects($this->once())->method('publish')->with($update)->willReturn('urn:uuid:1');

        $hub = new SameOriginHub($inner, new RequestStack());

        $this->assertSame('urn:uuid:1', $hub->publish($update));
    }

    private function innerHub(string $url): Hub
    {
        // No public URL configured, like config/packages/mercure.yaml after this change.
        return new Hub($url, new StaticTokenProvider('jwt'), new LcobucciFactory(str_repeat('s', 32)));
    }

    private function requestStack(Request $request): RequestStack
    {
        $stack = new RequestStack();
        $stack->push($request);

        return $stack;
    }
}
