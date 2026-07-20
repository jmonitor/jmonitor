<?php

declare(strict_types=1);

namespace App\Tests\Entity\Enums;

use App\Bridge\InfluxDb\RetentionDuration;
use App\Entity\Enums\Plan;
use PHPUnit\Framework\TestCase;

class PlanTest extends TestCase
{
    public function testSelfHostedHasHighestScore(): void
    {
        foreach ([Plan::FREE, Plan::PRO, Plan::MAX] as $plan) {
            $this->assertGreaterThan($plan->score(), Plan::SELF_HOSTED->score());
        }
    }

    public function testSelfHostedIsNotPurchasable(): void
    {
        $this->assertNotContains(Plan::SELF_HOSTED, Plan::orderedCases());
        $this->assertNotContains(Plan::SELF_HOSTED->value, Plan::stringCases());
        $this->assertFalse(Plan::SELF_HOSTED->isPurchasable());
        $this->assertNull(Plan::SELF_HOSTED->upgradeTo());
    }

    public function testSelfHostedUnlocksAllFeatures(): void
    {
        $this->assertTrue(Plan::SELF_HOSTED->autoRefresh());
        $this->assertTrue(Plan::SELF_HOSTED->historyChart());
        $this->assertTrue(Plan::SELF_HOSTED->alerting());
        $this->assertTrue(Plan::SELF_HOSTED->embedable());
        $this->assertTrue(Plan::SELF_HOSTED->noAds());
    }

    public function testSelfHostedLimits(): void
    {
        $this->assertSame(10, Plan::SELF_HOSTED->pushInterval());
        $this->assertSame(PHP_INT_MAX, Plan::SELF_HOSTED->nbRedisDb());
        $this->assertSame(PHP_INT_MAX, Plan::SELF_HOSTED->nbFrankenPhpWorkers());
        $this->assertSame(RetentionDuration::YEAR->asSeconds(1), Plan::SELF_HOSTED->influxDataRetentionSecond());
    }

    /**
     * Every method with an exhaustive match must have a SELF_HOSTED arm
     * (otherwise UnhandledMatchError at runtime).
     */
    public function testAllMatchArmsHandleSelfHosted(): void
    {
        $this->assertSame('Self-hosted', Plan::SELF_HOSTED->label());
        $this->assertSame('Self-hosted edition', Plan::SELF_HOSTED->subtitle());
        $this->assertSame(0, Plan::SELF_HOSTED->subscribeMonthlyPrice());
        $this->assertSame([], Plan::SELF_HOSTED->cardFeatures());
        $this->assertSame('server-moustaches', Plan::SELF_HOSTED->icon());
        $this->assertSame('1 year', Plan::SELF_HOSTED->dataRetention());
    }

    public function testPurchasablePlansArePaidCloudPlans(): void
    {
        $this->assertFalse(Plan::FREE->isPurchasable());
        $this->assertTrue(Plan::PRO->isPurchasable());
        $this->assertTrue(Plan::MAX->isPurchasable());
    }

    public function testPricingPageContent(): void
    {
        $this->assertSame(5, Plan::PRO->subscribeMonthlyPrice());
        $this->assertSame(10, Plan::MAX->subscribeMonthlyPrice());
        $this->assertSame('Ideal for solo devs and agencies', Plan::PRO->subtitle());
        $this->assertSame('Ideal for product teams', Plan::MAX->subtitle());
        $this->assertContains('Data retention: 2 months', Plan::PRO->cardFeatures());
        $this->assertSame(['Everything from Pro', 'Push interval: 10s', 'Data retention: 6 months'], Plan::MAX->cardFeatures());
    }
}
