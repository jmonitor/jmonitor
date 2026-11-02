<?php

declare(strict_types=1);

namespace App\Tests\Twig;

use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Finder\Finder;
use Twig\Environment;

final class ComponentTagsCompileTest extends KernelTestCase
{
    #[DataProvider('templates')]
    public function testNoComponentTagIsLeftUncompiled(string $template): void
    {
        $twig = self::getContainer()->get(Environment::class);

        $compiled = $twig->compileSource($twig->getLoader()->getSourceContext($template));

        $this->assertStringNotContainsString('<twig:', $compiled, 'Write the blocks of an HTML component as <twig:block name="...">, not {% block %}.');
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function templates(): iterable
    {
        $finder = new Finder()->files()->in(__DIR__ . '/../../templates')->name('*.twig');

        foreach ($finder as $file) {
            $name = str_replace('\\', '/', $file->getRelativePathname());

            yield $name => [$name];
        }
    }
}
