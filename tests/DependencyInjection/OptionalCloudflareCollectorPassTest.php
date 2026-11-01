<?php

declare(strict_types=1);

namespace App\Tests\DependencyInjection;

use App\DependencyInjection\OptionalCloudflareCollectorPass;
use Jmonitor\Collector\Cloudflare\CloudflareCollector;
use Jmonitor\Collector\System\SystemCollector;
use Jmonitor\Jmonitor;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

final class OptionalCloudflareCollectorPassTest extends TestCase
{
    private const array ENV = ['CLOUDFLARE_API_TOKEN', 'CLOUDFLARE_ZONE'];

    /** @var array<string, string|null> */
    private array $original = [];

    protected function setUp(): void
    {
        foreach (self::ENV as $name) {
            $this->original[$name] = $_ENV[$name] ?? null;
        }
    }

    protected function tearDown(): void
    {
        foreach ($this->original as $name => $value) {
            if ($value === null) {
                unset($_ENV[$name], $_SERVER[$name]);
            } else {
                $_ENV[$name] = $_SERVER[$name] = $value;
            }
        }
    }

    public function testTheCollectorStaysWhenBothVariablesAreSet(): void
    {
        $container = $this->container('token', 'example.com');

        new OptionalCloudflareCollectorPass()->process($container);

        $this->assertTrue($container->hasDefinition(CloudflareCollector::class));
        $this->assertSame([SystemCollector::class, CloudflareCollector::class], $this->addedCollectors($container));
    }

    /**
     * @return iterable<string, array{?string, ?string}>
     */
    public static function incomplete(): iterable
    {
        yield 'both empty' => ['', ''];
        yield 'no zone' => ['token', ' '];
        yield 'no token' => [null, 'example.com'];
    }

    #[DataProvider('incomplete')]
    public function testTheCollectorIsDroppedWithoutCredentials(?string $token, ?string $zone): void
    {
        $container = $this->container($token, $zone);

        new OptionalCloudflareCollectorPass()->process($container);

        $this->assertFalse($container->hasDefinition(CloudflareCollector::class));
        $this->assertSame([SystemCollector::class], $this->addedCollectors($container));
    }

    private function container(?string $token, ?string $zone): ContainerBuilder
    {
        foreach (array_combine(self::ENV, [$token, $zone]) as $name => $value) {
            if ($value === null) {
                unset($_ENV[$name], $_SERVER[$name]);
            } else {
                $_ENV[$name] = $_SERVER[$name] = $value;
            }
        }

        $container = new ContainerBuilder();
        $container->setDefinition(CloudflareCollector::class, new Definition(CloudflareCollector::class));
        $container->setDefinition(Jmonitor::class, (new Definition(Jmonitor::class))
            ->addMethodCall('addCollector', [new Reference(SystemCollector::class)])
            ->addMethodCall('addCollector', [new Reference(CloudflareCollector::class)]));

        return $container;
    }

    /**
     * @return string[]
     */
    private function addedCollectors(ContainerBuilder $container): array
    {
        return array_map(
            static fn(array $call): string => (string) $call[1][0],
            $container->getDefinition(Jmonitor::class)->getMethodCalls(),
        );
    }
}
