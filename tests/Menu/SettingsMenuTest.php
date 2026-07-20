<?php

declare(strict_types=1);

namespace App\Tests\Menu;

use App\Entity\Project;
use App\Menu\Menu\SettingsMenu;
use App\Plan\Edition;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;

class SettingsMenuTest extends TestCase
{
    /**
     * @return list<string>
     */
    private function labels(Edition $edition): array
    {
        $security = $this->createMock(Security::class);
        $security->method('isGranted')->willReturn(true);

        $menu = new SettingsMenu($security, $edition);

        $labels = [];
        foreach ($menu->getLinks(['project' => new Project()]) as $link) {
            $labels[] = $link->getLabel();
        }

        return $labels;
    }

    public function testPlanLinkVisibleInCloud(): void
    {
        $this->assertContains('Plan', $this->labels(Edition::CLOUD));
    }

    public function testPlanLinkHiddenInSelfHosted(): void
    {
        $labels = $this->labels(Edition::SELF_HOSTED);

        $this->assertNotContains('Plan', $labels);
        $this->assertContains('Project', $labels);
        $this->assertContains('Alerts', $labels);
        $this->assertContains('Team', $labels);
        $this->assertContains('API key', $labels);
    }
}
