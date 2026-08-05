<?php

declare(strict_types=1);

namespace App\Tests\Bridge\Mercure;

use App\Bridge\Mercure\MercureTopicProvider;
use App\Bridge\Mercure\SameOriginHub;
use App\Entity\Project;
use App\Project\ProjectContext;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Cookie;
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
    private const INTERNAL_HUB_URL = 'http://app/.well-known/mercure';
    private const SECRET = '!ChangeThisMercureHubJWTSecretKey!';

    public function testReturnsSameOriginSubscribeUrlAndScopesCookieToProjectTopic(): void
    {
        $project = new Project();
        $expectedTopic = sprintf('https://%s/metrics/consumed/%s', self::DOMAIN, $project->getUuid());

        $request = Request::create('https://dash.jmonitor.io/dash/projects');
        $provider = $this->createProvider($this->requestStack($request));

        $url = $provider->getConsumedMetricSubscribeUrl($project);

        // Relative on purpose: the browser resolves it against the page it is on, so the
        // subscription follows the real scheme, host and port with nothing to configure.
        $this->assertSame(SameOriginHub::PATH . '?topic=' . rawurlencode($expectedTopic), $url);

        $claims = $this->decodeJwtClaims((string) $this->cookie($request)->getValue());
        $this->assertSame([$expectedTopic], $claims['mercure']['subscribe']);
        // The subscriber cookie must not grant any publish rights.
        $this->assertSame([], $claims['mercure']['publish']);
    }

    public function testCookieIsNotSecureOverPlainHttp(): void
    {
        $request = Request::create('http://dash.jmonitor.localhost:8080/dash/projects');
        $provider = $this->createProvider($this->requestStack($request));

        $provider->getConsumedMetricSubscribeUrl(new Project());

        $cookie = $this->cookie($request);
        // A Secure cookie is dropped by the browser over plain HTTP: the subscription
        // would then be anonymous and rejected by the hub.
        $this->assertFalse($cookie->isSecure());
        $this->assertSame(SameOriginHub::PATH, $cookie->getPath());
        // Host-only: the cookie follows the dashboard host, whatever it is.
        $this->assertNull($cookie->getDomain());
    }

    public function testCookieIsSecureOverHttps(): void
    {
        $request = Request::create('https://dash.jmonitor.io/dash/projects');
        $provider = $this->createProvider($this->requestStack($request));

        $provider->getConsumedMetricSubscribeUrl(new Project());

        $this->assertTrue($this->cookie($request)->isSecure());
    }

    public function testDoesNotFailWhenCookieAlreadySet(): void
    {
        $project = new Project();
        $request = Request::create('https://dash.jmonitor.io/dash/projects');

        $logger = $this->createMock(LoggerInterface::class);
        // The second render (cookie already set on the request) is logged, not propagated.
        $logger->expects($this->once())->method('warning');

        $provider = $this->createProvider($this->requestStack($request), $logger);

        $provider->getConsumedMetricSubscribeUrl($project);
        $url = $provider->getConsumedMetricSubscribeUrl($project);

        $this->assertStringStartsWith(SameOriginHub::PATH . '?topic=', $url);
    }

    public function testDoesNotSetCookieWithoutRequest(): void
    {
        $provider = $this->createProvider(new RequestStack());

        $url = $provider->getConsumedMetricSubscribeUrl(new Project());

        $this->assertStringStartsWith(SameOriginHub::PATH . '?topic=', $url);
    }

    public function testPublicSubscribeUrlScopesJwtToTheComponentSubTopicOnly(): void
    {
        $project = new Project();
        $projectTopic = sprintf('https://%s/metrics/consumed/%s', self::DOMAIN, $project->getUuid());
        $componentTopic = $projectTopic . '/mysql';

        // No request: public URL generation must not depend on a request/cookie.
        $provider = $this->createProvider(new RequestStack());

        $url = $provider->getPublicConsumedMetricSubscribeUrl('mysql', $project);

        $this->assertStringStartsWith(
            SameOriginHub::PATH . '?topic=' . rawurlencode($componentTopic) . '&authorization=',
            $url,
        );

        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);
        $claims = $this->decodeJwtClaims((string) $query['authorization']);

        // Scoped to the embedded component's sub-topic, never the project-wide topic:
        // a public viewer must not be able to read the whole project's component stream.
        $this->assertSame([$componentTopic], $claims['mercure']['subscribe']);
        $this->assertNotContains($projectTopic, $claims['mercure']['subscribe']);
        $this->assertSame([], $claims['mercure']['publish']);
        $this->assertArrayHasKey('exp', $claims, 'Public JWTs must expire to bound the impact of a leak');
    }

    private function createProvider(RequestStack $requestStack, ?LoggerInterface $logger = null): MercureTopicProvider
    {
        // No public URL configured, like config/packages/mercure.yaml: the decorator
        // derives it from the request, which is what shapes the authorization cookie.
        $hub = new SameOriginHub(
            new Hub(self::INTERNAL_HUB_URL, new StaticTokenProvider('jwt'), new LcobucciFactory(self::SECRET)),
            $requestStack,
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

    private function cookie(Request $request): Cookie
    {
        $cookies = $request->attributes->get('_mercure_authorization_cookies');
        $this->assertIsArray($cookies);
        $this->assertArrayHasKey('', $cookies, 'A cookie must be set for the default hub');

        return $cookies[''];
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeJwtClaims(string $jwt): array
    {
        $payload = explode('.', $jwt)[1];
        $decoded = json_decode(base64_decode(strtr($payload, '-_', '+/'), true), true);

        $this->assertIsArray($decoded);

        return $decoded;
    }
}
