<?php

declare(strict_types=1);

namespace App\Tests\Controller\ValueResolver;

use App\Controller\ValueResolver\EmbedDtoValueResolver;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * The controller tests call actions directly rather than through the HTTP kernel, so
 * de-registering EmbedDtoValueResolver or renaming MapEmbedDto would fail no test without
 * this one: it inspects the compiled container's own definition of the service.
 */
class EmbedDtoValueResolverRegistrationTest extends KernelTestCase
{
    public function testItIsTaggedAsAnArgumentValueResolver(): void
    {
        self::bootKernel();

        // Dumped automatically by ContainerBuilderDebugDumpPass whenever the kernel runs in
        // debug mode (true in the test env here), for the same "debug:container" introspection
        // this test needs: the runtime container no longer carries tag metadata once compiled.
        $dumpFile = self::getContainer()->getParameter('debug.container.dump');
        $this->assertNotSame(false, $dumpFile, 'Container introspection requires debug mode.');

        $serializedContainerFile = substr_replace((string) $dumpFile, '.ser', -4);
        $this->assertFileExists($serializedContainerFile);

        $builder = unserialize(file_get_contents($serializedContainerFile), ['allowed_classes' => true]);
        $this->assertInstanceOf(ContainerBuilder::class, $builder);

        $definition = $builder->getDefinition(EmbedDtoValueResolver::class);

        $this->assertTrue(
            $definition->hasTag('controller.argument_value_resolver'),
            'EmbedDtoValueResolver must stay tagged as controller.argument_value_resolver for #[MapEmbedDto] to work.',
        );
    }
}
