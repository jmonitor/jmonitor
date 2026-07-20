<?php

declare(strict_types=1);

namespace App\Tests\Controller\Admin;

use App\Controller\Admin\SubscriptionCrudController;
use App\Entity\Enums\Plan;
use App\Entity\Project;
use App\Entity\Subscription;
use App\Plan\Edition;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class SubscriptionCrudControllerTest extends TestCase
{
    public function testConfigureCrudThrows404InSelfHosted(): void
    {
        $controller = new SubscriptionCrudController(Edition::SELF_HOSTED);

        $this->expectException(NotFoundHttpException::class);
        $controller->configureCrud(Crud::new());
    }

    public function testConfigureCrudPassesInCloud(): void
    {
        $controller = new SubscriptionCrudController(Edition::CLOUD);
        $crud = Crud::new();

        $this->assertSame($crud, $controller->configureCrud($crud));
    }

    public function testDeleteIsRefusedWhenTheProjectHasABucket(): void
    {
        $project = new Project();
        $project->setBucketId('c56923edbd145a7d');
        $subscription = new Subscription();
        $subscription->setPlan(Plan::PRO);
        $subscription->setProject($project);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->never())->method('remove');
        $em->expects($this->never())->method('flush');

        $this->expectException(\LogicException::class);
        new SubscriptionCrudController(Edition::CLOUD)->deleteEntity($em, $subscription);
    }

    public function testDeleteIsRefusedForAStripeBackedSubscription(): void
    {
        $subscription = new Subscription();
        $subscription->setPlan(Plan::PRO);
        $subscription->setStripeSubscriptionId('sub_1234567890');
        $subscription->setProject(new Project());

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->never())->method('remove');
        $em->expects($this->never())->method('flush');

        $this->expectException(\LogicException::class);
        new SubscriptionCrudController(Edition::CLOUD)->deleteEntity($em, $subscription);
    }

    /**
     * Project owns the FK (project.subscription_id): removing the subscription without
     * clearing the owning side leaves a dangling reference and MySQL rejects the DELETE.
     */
    public function testDeleteEntityClearsTheProjectReference(): void
    {
        $project = new Project();
        $subscription = new Subscription();
        $subscription->setPlan(Plan::PRO);
        $subscription->setProject($project);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())->method('remove')->with($subscription);
        $em->expects($this->once())->method('flush');

        new SubscriptionCrudController(Edition::CLOUD)->deleteEntity($em, $subscription);

        $this->assertNull($project->getSubscription());
        $this->assertNull($subscription->getProject());
    }

    public function testDeleteEntityWorksOnASubscriptionWithoutProject(): void
    {
        $subscription = new Subscription();
        $subscription->setPlan(Plan::PRO);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())->method('remove')->with($subscription);
        $em->expects($this->once())->method('flush');

        new SubscriptionCrudController(Edition::CLOUD)->deleteEntity($em, $subscription);

        $this->assertNull($subscription->getProject());
    }
}
