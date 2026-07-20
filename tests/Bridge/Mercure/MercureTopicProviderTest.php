<?php

declare(strict_types=1);

namespace App\Tests\Bridge\Mercure;

use App\Bridge\Mercure\MercureTopicProvider;
use App\Entity\Project;
use App\Project\ProjectContext;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Mercure\Authorization;
use Symfony\Component\Mercure\Hub;
use Symfony\Component\Mercure\HubRegistry;
use Symfony\Component\Mercure\Jwt\LcobucciFactory;
use Symfony\Component\Mercure\Jwt\StaticTokenProvider;

class MercureTopicProviderTest extends TestCase
{
    private const DOMAIN = 'jmonitor.io';
    private const HUB_URL = 'https://dash.jmonitor.io/.well-known/mercure';
    private const SECRET = '!ChangeThisMercureHubJWTSecretKey!';

    public function testReturnsSubscribeUrlAndScopesCookieToProjectTopic(): void
    {
        $project = new Project();
        $expectedTopic = sprintf('https://%s/metrics/consumed/%s', self::DOMAIN, $project->getUuid());

        $request = Request::create(self::HUB_URL);
        $provider = $this->createProvider($this->requestStack($request));

        $url = $provider->getConsumedMetricSubscribeUrl($project);

        $this->assertSame(self::HUB_URL . '?topic=' . rawurlencode($expectedTopic), $url);

        $claims = $this->extractCookieClaims($request);
        $this->assertSame([$expectedTopic], $claims['mercure']['subscribe']);
        // The subscriber cookie must not grant any publish rights.
        $this->assertSame([], $claims['mercure']['publish']);
    }

    public function testDoesNotFailWhenCookieAlreadySet(): void
    {
        $project = new Project();
        $request = Request::create(self::HUB_URL);

        $logger = $this->createMock(LoggerInterface::class);
        // The second render (cookie already set on the request) is logged, not propagated.
        $logger->expects($this->once())->method('warning');

        $provider = $this->createProvider($this->requestStack($request), $logger);

        $provider->getConsumedMetricSubscribeUrl($project);
        $url = $provider->getConsumedMetricSubscribeUrl($project);

        $this->assertStringStartsWith(self::HUB_URL . '?topic=', $url);
    }

    public function testDoesNotSetCookieWithoutRequest(): void
    {
        $provider = $this->createProvider(new RequestStack());

        $url = $provider->getConsumedMetricSubscribeUrl(new Project());

        $this->assertStringStartsWith(self::HUB_URL . '?topic=', $url);
    }

    private function createProvider(RequestStack $requestStack, ?LoggerInterface $logger = null): MercureTopicProvider
    {
        $hub = new Hub(
            self::HUB_URL,
            new StaticTokenProvider('jwt'),
            new LcobucciFactory(self::SECRET),
            self::HUB_URL,
        );

        return new MercureTopicProvider(
            self::DOMAIN,
            $this->createMock(ProjectContext::class),
            $hub,
            new Authorization(new HubRegistry($hub)),
            $requestStack,
            $logger ?? $this->createMock(LoggerInterface::class),
        );
    }

    private function requestStack(Request $request): RequestStack
    {
        $stack = new RequestStack();
        $stack->push($request);

        return $stack;
    }

    /**
     * @return array<string, mixed>
     */
    private function extractCookieClaims(Request $request): array
    {
        $cookies = $request->attributes->get('_mercure_authorization_cookies');
        $this->assertIsArray($cookies);
        $this->assertArrayHasKey('', $cookies, 'A cookie must be set for the default hub');

        $jwt = $cookies['']->getValue();
        $payload = explode('.', (string) $jwt)[1];
        $decoded = json_decode(base64_decode(strtr($payload, '-_', '+/'), true), true);

        $this->assertIsArray($decoded);

        return $decoded;
    }
}
