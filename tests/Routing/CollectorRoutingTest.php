<?php

declare(strict_types=1);

namespace App\Tests\Routing;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\RouterInterface;

class CollectorRoutingTest extends KernelTestCase
{
    // Same precaution as EditionRoutingTest: Dotenv populates $_SERVER/$_ENV once at
    // process bootstrap and never reloads, so capture and restore instead of unsetting.
    private string|false $originalInternalHost = false;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->originalInternalHost = $_SERVER['JMONITOR_COLLECTOR_INTERNAL_HOST'] ?? $_ENV['JMONITOR_COLLECTOR_INTERNAL_HOST'] ?? false;
    }

    #[\Override]
    protected function tearDown(): void
    {
        if (false === $this->originalInternalHost) {
            unset($_SERVER['JMONITOR_COLLECTOR_INTERNAL_HOST'], $_ENV['JMONITOR_COLLECTOR_INTERNAL_HOST']);
        } else {
            $_SERVER['JMONITOR_COLLECTOR_INTERNAL_HOST'] = $_ENV['JMONITOR_COLLECTOR_INTERNAL_HOST'] = $this->originalInternalHost;
        }
        parent::tearDown();
    }

    public function testPublicCollectorHostMatches(): void
    {
        self::bootKernel();

        $this->assertSame('collector', $this->match('collector.jmonitor.io')['_route']);
    }

    public function testAnInternalHostIsNotAcceptedByDefault(): void
    {
        self::bootKernel();

        $this->expectException(ResourceNotFoundException::class);
        $this->match('app');
    }

    public function testInternalHostMatchesWhenConfigured(): void
    {
        // The self-hosted collector container cannot post to the public host when
        // APP_DOMAIN is under .localhost: libcurl resolves *.localhost to loopback
        // (RFC 6761) and never asks Docker DNS. It uses the compose service name instead.
        $_SERVER['JMONITOR_COLLECTOR_INTERNAL_HOST'] = $_ENV['JMONITOR_COLLECTOR_INTERNAL_HOST'] = 'app';
        self::bootKernel();

        $this->assertSame('internal_collector', $this->match('app')['_route']);
    }

    public function testPublicHostStillMatchesWhenAnInternalHostIsConfigured(): void
    {
        // Agents on other servers keep posting to collector.<domain>: the internal host
        // is an addition, never a replacement.
        $_SERVER['JMONITOR_COLLECTOR_INTERNAL_HOST'] = $_ENV['JMONITOR_COLLECTOR_INTERNAL_HOST'] = 'app';
        self::bootKernel();

        $this->assertSame('collector', $this->match('collector.jmonitor.io')['_route']);
    }

    /**
     * @return array<string, mixed>
     */
    private function match(string $host): array
    {
        /** @var RouterInterface $router */
        $router = self::getContainer()->get(RouterInterface::class);
        $router->getContext()->setHost($host);
        // The collector endpoint is POST-only; a GET context would raise
        // MethodNotAllowedException instead of matching.
        $router->getContext()->setMethod('POST');

        return $router->match('/metrics');
    }
}
