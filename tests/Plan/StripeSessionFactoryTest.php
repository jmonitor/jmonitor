<?php

declare(strict_types=1);

namespace App\Tests\Plan;

use App\Entity\Enums\Plan;
use App\Entity\Order;
use App\Entity\Project;
use App\Entity\Subscription;
use App\Entity\User;
use App\Plan\StripeSessionFactory;
use App\Project\ProjectContext;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;
use Stripe\Checkout\Session;
use Stripe\StripeClient;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class StripeSessionFactoryTest extends TestCase
{
    public function testTrialDaysAreCreditedWhenActiveNonStripeSubscription(): void
    {
        $project = new Project();
        $endAt = new \DateTimeImmutable('+5 days midnight');
        $this->givenSubscription($project, $endAt, stripeSubscriptionId: null);

        $params = $this->createSessionAndCaptureParams($project);

        $this->assertSame($endAt->getTimestamp(), $params['subscription_data']['trial_end'] ?? null);
        $this->assertArrayHasKey('metadata', $params['subscription_data']);
    }

    public function testNoTrialEndWhenTrialEndsInLessThan48Hours(): void
    {
        $project = new Project();
        $this->givenSubscription($project, new \DateTimeImmutable('+1 day midnight'), stripeSubscriptionId: null);

        $params = $this->createSessionAndCaptureParams($project);

        $this->assertArrayNotHasKey('trial_end', $params['subscription_data']);
    }

    public function testNoTrialEndWhenActiveSubscriptionIsStripeBased(): void
    {
        $project = new Project();
        $this->givenSubscription($project, new \DateTimeImmutable('+20 days midnight'), stripeSubscriptionId: 'sub_123');

        $params = $this->createSessionAndCaptureParams($project);

        $this->assertArrayNotHasKey('trial_end', $params['subscription_data']);
    }

    public function testNoTrialEndWhenNoActiveSubscription(): void
    {
        $project = new Project();

        $params = $this->createSessionAndCaptureParams($project);

        $this->assertArrayNotHasKey('trial_end', $params['subscription_data']);
    }

    private function givenSubscription(Project $project, \DateTimeImmutable $endAt, ?string $stripeSubscriptionId): void
    {
        $subscription = new Subscription();
        $subscription->setPlan(Plan::PRO);
        $subscription->setEndAt($endAt);
        $subscription->setAutoRenew(false);
        $subscription->setStripeSubscriptionId($stripeSubscriptionId);
        $subscription->setProject($project);
    }

    public function testProSessionUsesProPriceAndMetadata(): void
    {
        $params = $this->createSessionAndCaptureParams(new Project(), Plan::PRO);

        $this->assertSame('price_pro_123', $params['line_items'][0]['price']);
        $this->assertSame('pro', $params['metadata']['plan']);
    }

    public function testMaxSessionUsesMaxPriceAndMetadata(): void
    {
        $params = $this->createSessionAndCaptureParams(new Project(), Plan::MAX);

        $this->assertSame('price_max_123', $params['line_items'][0]['price']);
        $this->assertSame('max', $params['metadata']['plan']);
    }

    public function testThrowsForNonPurchasablePlan(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->createSessionAndCaptureParams(new Project(), Plan::FREE);
    }

    /**
     * @return mixed[] the params sent to checkout->sessions->create
     */
    private function createSessionAndCaptureParams(Project $project, Plan $plan = Plan::PRO): array
    {
        $capturedParams = null;

        $stripe = $this->getMockBuilder(StripeClient::class)
            ->setConstructorArgs(['sk_test_123'])
            ->onlyMethods(['request'])
            ->getMock();
        $stripe->method('request')
            ->willReturnCallback(function (string $method, string $path, array $params) use (&$capturedParams) {
                $capturedParams = $params;

                return Session::constructFrom(['url' => 'https://checkout.stripe.test/session']);
            });

        $security = $this->createMock(Security::class);
        $security->method('getUser')->willReturn(new User());

        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $urlGenerator->method('generate')->willReturn('https://dash.jmonitor.test/url');

        $projectContext = $this->createMock(ProjectContext::class);
        $projectContext->method('getCurrentProject')->willReturn($project);

        $orderRepository = $this->createMock(EntityRepository::class);
        $orderRepository->method('findOneBy')->willReturn(null);
        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getRepository')->with(Order::class)->willReturn($orderRepository);

        $factory = new StripeSessionFactory('price_pro_123', 'price_max_123', $stripe, $security, $urlGenerator, $projectContext, $em);

        $url = $factory->createSession($plan);
        $this->assertSame('https://checkout.stripe.test/session', $url);
        $this->assertIsArray($capturedParams);

        return $capturedParams;
    }
}
