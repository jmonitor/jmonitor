<?php

declare(strict_types=1);

namespace App\Tests\Twig\Components;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Finder\Finder;
use Twig\Environment;

final class InfoLineTest extends KernelTestCase
{
    public function testTheValuePropIsEscaped(): void
    {
        $html = $this->render('<twig:InfoLine label="Hostname" value="{{ hostname }}" />', ['hostname' => '<b>web-01</b>']);

        $this->assertStringNotContainsString('<b>', $html);
        $this->assertSame('<b>web-01</b>', trim(new Crawler($html)->filter('.dt')->text()));
    }

    public function testTheContentFormKeepsItsMarkup(): void
    {
        $html = $this->render('<twig:InfoLine label="Data">{{ size|format_bytes }}</twig:InfoLine>', ['size' => 2048]);

        $this->assertSame('KiB', new Crawler($html)->filter('.dt span.unit')->text());
    }

    public function testNoTemplatePassesAFormattedAmountThroughTheValueProp(): void
    {
        $offenders = [];

        foreach (new Finder()->files()->in(__DIR__ . '/../../../templates')->name('*.twig')->contains('/<twig:InfoLine\b[^>]*\bvalue="[^"]*\|\s*format_(bytes|ms)\b/') as $file) {
            $offenders[] = $file->getRelativePathname();
        }

        $this->assertSame([], $offenders, 'A formatted amount carries markup, which the value prop escapes: write it between the tags.');
    }

    /**
     * @param array<string, mixed> $context
     */
    private function render(string $template, array $context): string
    {
        self::bootKernel();

        return self::getContainer()->get(Environment::class)->createTemplate($template)->render($context);
    }
}
