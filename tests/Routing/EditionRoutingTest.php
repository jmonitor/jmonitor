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
