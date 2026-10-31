<?php

declare(strict_types=1);

namespace App\Tests\Entity\Enums;

use App\Entity\Enums\Component;
use App\Metrics\Consumer\Consumer;
use App\Metrics\Dto\Bag\Cloudflare\CloudflareBag;
use PHPUnit\Framework\TestCase;

final class ComponentTest extends TestCase
{
    public function testEveryCaseIsInEveryOrderedList(): void
    {
        foreach ([Component::alphaOrderedCases(), Component::menuOrderedCases(), Component::pricingOrderedCases()] as $list) {
            $this->assertEqualsCanonicalizing(Component::cases(), $list);
        }
    }

    public function testCloudflareClosesTheMenuAndIsAlphabeticallyPlaced(): void
    {
        $menu = Component::menuOrderedCases();
        $this->assertSame(Component::Cloudflare, end($menu));

        $alpha = Component::alphaOrderedCases();
        $this->assertSame(Component::Cloudflare, $alpha[array_search(Component::Caddy, $alpha, true) + 1]);
    }

    public function testTheDemoProjectDoesNotGetCloudflare(): void
    {
        $this->assertNotContains(Component::Cloudflare, Component::demoCases());
        $this->assertCount(count(Component::cases()) - 1, Component::demoCases());
    }

    public function testCloudflareConsumerMapsBackToTheComponent(): void
    {
        $this->assertSame([Consumer::CLOUDFLARE], Component::Cloudflare->consumers());
        $this->assertSame(Component::Cloudflare, Consumer::CLOUDFLARE->getComponent());
        $this->assertSame(CloudflareBag::class, Consumer::CLOUDFLARE->metricBagClass());
        $this->assertSame(1, Consumer::CLOUDFLARE->latestVersion());
    }
}
