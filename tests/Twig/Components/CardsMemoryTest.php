<?php

declare(strict_types=1);

namespace App\Tests\Twig\Components;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DomCrawler\Crawler;
use Twig\Environment;

final class CardsMemoryTest extends KernelTestCase
{
    public function testMemoryWiresTheTabsToTheController(): void
    {
        $html = $this->render(<<<'TWIG'
            <twig:Cards :titles="['Traffic', 'Web Analytics']" memory="true">
                {% block block_1 %}one{% endblock %}
                {% block block_2 %}two{% endblock %}
            </twig:Cards>
            TWIG);

        $crawler = new Crawler($html);

        $this->assertSame('tabs-memory', $crawler->filter('[data-controller]')->first()->attr('data-controller'));
        $this->assertSame(['traffic', 'web-analytics'], $crawler->filter('[data-tabs-memory-target="tab"]')->each(fn(Crawler $tab): ?string => $tab->attr('data-tabs-memory-key-param')));
    }

    public function testWithoutMemoryNothingChanges(): void
    {
        $html = $this->render(<<<'TWIG'
            <twig:Cards :titles="['Traffic', 'Web Analytics']">
                {% block block_1 %}one{% endblock %}
                {% block block_2 %}two{% endblock %}
            </twig:Cards>
            TWIG);

        $this->assertStringNotContainsString('tabs-memory', $html);
        $this->assertSame(2, new Crawler($html)->filter('.nav-link')->count());
    }

    private function render(string $template): string
    {
        self::bootKernel();

        return self::getContainer()->get(Environment::class)->createTemplate($template)->render();
    }
}
