<?php

declare(strict_types=1);

namespace App\Tests\Twig\Components;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DomCrawler\Crawler;
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

    /**
     * @param array<string, mixed> $context
     */
    private function render(string $template, array $context): string
    {
        self::bootKernel();

        return self::getContainer()->get(Environment::class)->createTemplate($template)->render($context);
    }
}
