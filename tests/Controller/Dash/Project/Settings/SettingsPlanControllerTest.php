<?php

declare(strict_types=1);

namespace App\Tests\Controller\Dash\Project\Settings;

use App\Controller\Dash\Project\Settings\SettingsPlanController;
use App\Entity\Enums\Plan;
use App\Entity\Project;
use App\Entity\Subscription;
use App\Plan\StripeSessionFactory;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Clock\MockClock;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\RouterInterface;

class SettingsPlanControllerTest extends TestCase
{
    private Session $session;

    public function testSubscribeToFreeIs404(): void
    {
        $this->expectException(NotFoundHttpException::class);

        $controller = new SettingsPlanController();
        $controller->subscribe(new Project(), Plan::FREE, $this->stripeSessionFactoryNeverCalled());
    }

    public function testSubscribeToSelfHostedIs404(): void
    {
        $this->expectException(NotFoundHttpException::class);

        $controller = new SettingsPlanController();
        $controller->subscribe(new Project(), Plan::SELF_HOSTED, $this->stripeSessionFactoryNeverCalled());
    }

    public function testPaymentSuccessRedirectsImmediatelyWhenTheWebhookAlreadyLinkedTheSubscription(): void
    {
        $project = $this->projectWithSubscription('sub_123');
        $clock = new MockClock();
        $start = $clock->now();

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->never())->method('refresh');

        $response = $this->makeController()->paiementSuccess($project, $em, $clock);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('/plan', $response->getTargetUrl());
        $this->assertSame(['Payment successful, thank you! Your subscription is now active.'], $this->session->getFlashBag()->get('success'));
        $this->assertEquals($start, $clock->now(), 'No sleep expected');
    }

    public function testPaymentSuccessWaitsForTheWebhookToLinkTheSubscription(): void
    {
        // ongoing trial: a subscription exists but is not linked to Stripe yet
        $project = $this->projectWithSubscription(null);
        $clock = new MockClock();
        $start = $clock->now();

        // the webhook lands between the second and third DB check
        $subscriptionRefreshCount = 0;
        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('refresh')->willReturnCallback(function (object $entity) use (&$subscriptionRefreshCount): void {
            if ($entity instanceof Subscription && ++$subscriptionRefreshCount === 2) {
                $entity->setStripeSubscriptionId('sub_123');
            }
        });

        $response = $this->makeController()->paiementSuccess($project, $em, $clock);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame(['Payment successful, thank you! Your subscription is now active.'], $this->session->getFlashBag()->get('success'));
        $this->assertSame(2.0, (float) ($clock->now()->getTimestamp() - $start->getTimestamp()));
    }

    public function testPaymentSuccessGivesUpWaitingForTheWebhookAfterFiveSeconds(): void
    {
        $project = $this->projectWithSubscription(null);
        $clock = new MockClock();
        $start = $clock->now();

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('refresh');

        $response = $this->makeController()->paiementSuccess($project, $em, $clock);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame(['Payment successful, thank you! Your subscription will be active in a few moments.'], $this->session->getFlashBag()->get('success'));
        $this->assertSame(5.0, (float) ($clock->now()->getTimestamp() - $start->getTimestamp()));
    }

    private function projectWithSubscription(?string $stripeSubscriptionId): Project
    {
        $subscription = new Subscription();
        $subscription->setPlan(Plan::PRO);
        $subscription->setEndAt(new \DateTimeImmutable('+1 month'));
        $subscription->setStripeSubscriptionId($stripeSubscriptionId);

        $project = new Project();
        $project->setSubscription($subscription);

        return $project;
    }

    private function makeController(): SettingsPlanController
    {
        $this->session = new Session(new MockArraySessionStorage());
        $request = new Request();
        $request->setSession($this->session);
        $requestStack = new RequestStack();
        $requestStack->push($request);

        $router = $this->createMock(RouterInterface::class);
        $router->method('generate')->with('project.settings.plan')->willReturn('/plan');

        $container = new Container();
        $container->set('request_stack', $requestStack);
        $container->set('router', $router);

        $controller = new SettingsPlanController();
        $controller->setContainer($container);

        return $controller;
    }

    /**
     * The 404 guard must throw before ever reaching the factory, so it's never called.
     */
    private function stripeSessionFactoryNeverCalled(): StripeSessionFactory
    {
        $factory = $this->createMock(StripeSessionFactory::class);
        $factory->expects($this->never())->method('createSession');

        return $factory;
    }
}
