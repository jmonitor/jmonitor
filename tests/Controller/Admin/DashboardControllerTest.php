<?php

declare(strict_types=1);

namespace App\Tests\Controller\Admin;

use App\Controller\Admin\DashboardController;
use App\Plan\Edition;
use PHPUnit\Framework\TestCase;

class DashboardControllerTest extends TestCase
{
    /**
     * @return list<string>
     */
    private function menuLabels(Edition $edition): array
    {
        $controller = new DashboardController('jmonitor.io', $edition);

        $labels = [];
        foreach ($controller->configureMenuItems() as $item) {
            $labels[] = (string) $item->getAsDto()->getLabel();
        }

        return $labels;
    }

    public function testSubscriptionsMenuVisibleInCloud(): void
    {
        $this->assertContains('Subscriptions', $this->menuLabels(Edition::CLOUD));
    }

    public function testSubscriptionsMenuHiddenInSelfHosted(): void
    {
        $labels = $this->menuLabels(Edition::SELF_HOSTED);

        $this->assertNotContains('Subscriptions', $labels);
        $this->assertContains('Users', $labels);
        $this->assertContains('Projects', $labels);
    }
}
