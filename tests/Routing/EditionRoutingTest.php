<?php

declare(strict_types=1);

namespace App\Tests\Routing;

use App\Plan\Edition;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Matcher\RequestMatcherInterface;
use Symfony\Component\Routing\RouterInterface;

class EditionRoutingTest extends KernelTestCase
{
    // Dotenv only populates $_SERVER/$_ENV once, at process bootstrap (see tests/bootstrap.php) —
    // it is not reloaded between tests. Blindly unsetting APP_EDITION in tearDown() would wipe out
    // that bootstrap-provided default ("cloud", from .env) for every test running afterwards in the
    // same PHPUnit process (this file's own later tests, and any future kernel-booting test file).
    // So capture whatever was there before each test and restore exactly that afterwards.
    private string|false $originalAppEdition = false;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->originalAppEdition = $_SERVER['APP_EDITION'] ?? $_ENV['APP_EDITION'] ?? false;
    }

    #[\Override]
    protected function tearDown(): void
    {
        if (false === $this->originalAppEdition) {
            unset($_SERVER['APP_EDITION'], $_ENV['APP_EDITION']);
        } else {
            $_SERVER['APP_EDITION'] = $_ENV['APP_EDITION'] = $this->originalAppEdition;
        }
        parent::tearDown();
    }

    /**
     * @return array<string, mixed>
     */
    private function match(string $host, string $path): array
    {
        /** @var RouterInterface $router */
        $router = self::getContainer()->get(RouterInterface::class);
        $router->getContext()->setHost($host);

        return $router->match($path);
    }

    public function testEditionServiceDefaultsToCloud(): void
    {
        self::bootKernel();

        $this->assertSame(Edition::CLOUD, self::getContainer()->get(Edition::class));
    }

    public function testBillingRouteMatchesInCloud(): void
    {
        self::bootKernel();

        $parameters = $this->match('dash.jmonitor.io', '/p/0197fc70-0000-7000-8000-000000000000/settings/plan');

        $this->assertSame('project.settings.plan', $parameters['_route']);
    }

    public function testBillingRouteIs404InSelfHosted(): void
    {
        // Symfony's env resolution checks $_ENV before $_SERVER (EnvVarProcessor::getEnv()),
        // so both must be overridden or the bootstrap-loaded $_ENV value ("cloud") wins silently.
        $_SERVER['APP_EDITION'] = $_ENV['APP_EDITION'] = 'selfhosted';
        self::bootKernel();

        $this->expectException(ResourceNotFoundException::class);
        $this->match('dash.jmonitor.io', '/p/0197fc70-0000-7000-8000-000000000000/settings/plan');
    }

    public function testVersionUpdateRouteMatchesInSelfHosted(): void
    {
        // Symfony's env resolution checks $_ENV before $_SERVER (EnvVarProcessor::getEnv()),
        // so both must be overridden or the bootstrap-loaded $_ENV value ("cloud") wins silently.
        $_SERVER['APP_EDITION'] = $_ENV['APP_EDITION'] = 'selfhosted';
        self::bootKernel();

        $parameters = $this->match('dash.jmonitor.io', '/_version-update');

        $this->assertSame('version.update', $parameters['_route']);
    }

    /**
     * The cloud edition deploys from source and has no version to compare.
     */
    public function testVersionUpdateRouteIs404InCloud(): void
    {
        self::bootKernel();

        $this->expectException(ResourceNotFoundException::class);
        $this->match('dash.jmonitor.io', '/_version-update');
    }

    /**
     * Unlike the app's own version, an agent's packages are project-scoped and mean the
     * same thing in both editions.
     */
    public function testPackageUpdateRouteMatchesInCloud(): void
    {
        self::bootKernel();

        $parameters = $this->match('dash.jmonitor.io', '/p/0197fc70-0000-7000-8000-000000000000/_version-update/collector');

        $this->assertSame('project.version.update', $parameters['_route']);
        $this->assertSame('collector', $parameters['package']);
    }

    public function testPackageUpdateRouteMatchesInSelfHosted(): void
    {
        // Symfony's env resolution checks $_ENV before $_SERVER (EnvVarProcessor::getEnv()),
        // so both must be overridden or the bootstrap-loaded $_ENV value ("cloud") wins silently.
        $_SERVER['APP_EDITION'] = $_ENV['APP_EDITION'] = 'selfhosted';
        self::bootKernel();

        $parameters = $this->match('dash.jmonitor.io', '/p/0197fc70-0000-7000-8000-000000000000/_version-update/bundle');

        $this->assertSame('project.version.update', $parameters['_route']);
        $this->assertSame('bundle', $parameters['package']);
    }

    public function testSubscribeRouteIs404InSelfHosted(): void
    {
        // Symfony's env resolution checks $_ENV before $_SERVER (EnvVarProcessor::getEnv()),
        // so both must be overridden or the bootstrap-loaded $_ENV value ("cloud") wins silently.
        $_SERVER['APP_EDITION'] = $_ENV['APP_EDITION'] = 'selfhosted';
        self::bootKernel();

        $this->expectException(ResourceNotFoundException::class);
        $this->match('dash.jmonitor.io', '/p/0197fc70-0000-7000-8000-000000000000/settings/plan/subscribe/pro');
    }

    public function testWebhookRouteMatchesInCloud(): void
    {
        self::bootKernel();

        $parameters = $this->match('webhook.jmonitor.io', '/webhook/stripe');

        $this->assertSame('_webhook_controller', $parameters['_route']);
    }

    /**
     * The invitation entry point is the same in both editions: only the ability to
     * register *without* an invitation differs.
     */
    public function testJoinRouteMatchesInCloud(): void
    {
        self::bootKernel();

        $parameters = $this->match('dash.jmonitor.io', '/join/0123456789abcdef');

        $this->assertSame('invitation.join', $parameters['_route']);
    }

    public function testJoinRouteMatchesInSelfHosted(): void
    {
        // Symfony's env resolution checks $_ENV before $_SERVER (EnvVarProcessor::getEnv()),
        // so both must be overridden or the bootstrap-loaded $_ENV value ("cloud") wins silently.
        $_SERVER['APP_EDITION'] = $_ENV['APP_EDITION'] = 'selfhosted';
        self::bootKernel();

        $parameters = $this->match('dash.jmonitor.io', '/join/0123456789abcdef');

        $this->assertSame('invitation.join', $parameters['_route']);
    }

    /**
     * OAuth sign-in is cloud-only: wiring it up means a Google Cloud project per install,
     * and it is a second door creating accounts on the fly. The authenticator itself stays
     * registered — programmatic login after registration and password reset uses it.
     */
    public function testOauthRoutesMatchInCloud(): void
    {
        self::bootKernel();

        $this->assertSame('security.login.oauth', $this->match('dash.jmonitor.io', '/login/oauth')['_route']);
        $this->assertSame('security.login.oauth.check', $this->match('dash.jmonitor.io', '/login/oauth/check')['_route']);
    }

    public function testOauthLoginRouteIs404InSelfHosted(): void
    {
        // Symfony's env resolution checks $_ENV before $_SERVER (EnvVarProcessor::getEnv()),
        // so both must be overridden or the bootstrap-loaded $_ENV value ("cloud") wins silently.
        $_SERVER['APP_EDITION'] = $_ENV['APP_EDITION'] = 'selfhosted';
        self::bootKernel();

        $this->expectException(ResourceNotFoundException::class);
        $this->match('dash.jmonitor.io', '/login/oauth');
    }

    public function testOauthCheckRouteIs404InSelfHosted(): void
    {
        // Symfony's env resolution checks $_ENV before $_SERVER (EnvVarProcessor::getEnv()),
        // so both must be overridden or the bootstrap-loaded $_ENV value ("cloud") wins silently.
        $_SERVER['APP_EDITION'] = $_ENV['APP_EDITION'] = 'selfhosted';
        self::bootKernel();

        $this->expectException(ResourceNotFoundException::class);
        $this->match('dash.jmonitor.io', '/login/oauth/check');
    }

    /**
     * Registering through an invitation link stays reachable in both editions: it is the
     * only way in once self-hosted closes open registration. The bare /register route
     * matches everywhere too — the controller, not the router, turns it away.
     */
    public function testInvitationRegisterRouteMatchesInCloud(): void
    {
        self::bootKernel();

        $this->assertSame('security.register', $this->match('dash.jmonitor.io', '/register')['_route']);
        $this->assertSame('security.register.invitation', $this->match('dash.jmonitor.io', '/register/0123456789abcdef')['_route']);
    }

    public function testInvitationRegisterRouteMatchesInSelfHosted(): void
    {
        // Symfony's env resolution checks $_ENV before $_SERVER (EnvVarProcessor::getEnv()),
        // so both must be overridden or the bootstrap-loaded $_ENV value ("cloud") wins silently.
        $_SERVER['APP_EDITION'] = $_ENV['APP_EDITION'] = 'selfhosted';
        self::bootKernel();

        $this->assertSame('security.register', $this->match('dash.jmonitor.io', '/register')['_route']);
        $this->assertSame('security.register.invitation', $this->match('dash.jmonitor.io', '/register/0123456789abcdef')['_route']);
    }

    /**
     * Accepting an invitation mutates state and its link travels by e-mail:
     * a GET would let link scanners and prefetchers accept it silently.
     */
    public function testAcceptingAnInvitationRejectsGet(): void
    {
        self::bootKernel();

        /** @var RouterInterface $router */
        $router = self::getContainer()->get(RouterInterface::class);
        $router->getContext()->setHost('dash.jmonitor.io');

        $this->assertInstanceOf(RequestMatcherInterface::class, $router);

        $this->expectException(MethodNotAllowedException::class);
        $router->matchRequest(Request::create('https://dash.jmonitor.io/invitations/0123456789abcdef/accept', 'GET'));
    }

    public function testWebhookRouteIs404InSelfHosted(): void
    {
        // Symfony's env resolution checks $_ENV before $_SERVER (EnvVarProcessor::getEnv()),
        // so both must be overridden or the bootstrap-loaded $_ENV value ("cloud") wins silently.
        $_SERVER['APP_EDITION'] = $_ENV['APP_EDITION'] = 'selfhosted';
        self::bootKernel();

        $this->expectException(ResourceNotFoundException::class);
        $this->match('webhook.jmonitor.io', '/webhook/stripe');
    }
}
