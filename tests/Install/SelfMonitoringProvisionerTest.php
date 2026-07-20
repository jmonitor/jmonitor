<?php

declare(strict_types=1);

namespace App\Tests\Install;

use App\Entity\Enums\Component;
use App\Entity\Enums\ProjectRole;
use App\Entity\Project;
use App\Entity\ProjectUser;
use App\Entity\User;
use App\Install\SelfMonitoringProvisioner;
use App\Repository\ProjectRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

/**
 * The self-monitoring project is provisioned once (first boot of a self-hosted
 * instance) and identified by its well-known uuid, not by its name: the
 * admin may rename it freely. It is a NORMAL project (alerts stay active) with
 * no subscription (PlanResolver grants SELF_HOSTED to the whole edition).
 */
final class SelfMonitoringProvisionerTest extends TestCase
{
    public function testCreatesTheProjectOwnedByTheAdmin(): void
    {
        $repository = $this->createMock(ProjectRepository::class);
        $repository->method('findOneBy')->with(['uuid' => Uuid::fromString(Project::SELF_MONITORING_UUID)])->willReturn(null);

        $persisted = [];
        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('persist')->willReturnCallback(function (object $entity) use (&$persisted): void {
            $persisted[] = $entity;
        });
        $em->expects($this->once())->method('flush');

        $owner = new User();
        $project = new SelfMonitoringProvisioner($em, $repository)->provision($owner);

        self::assertSame(SelfMonitoringProvisioner::PROJECT_NAME, $project->getName());
        self::assertTrue($project->getUuid()->equals(Uuid::fromString(Project::SELF_MONITORING_UUID)));
        self::assertNotSame('', $project->getApiKey(), 'API key comes from the Project constructor.');
        self::assertNull($project->getSubscription(), 'No subscription: the edition-wide SELF_HOSTED plan applies.');

        // The whole self-hosted stack is collected (docker/selfhosted/compose.yaml).
        foreach ([Component::System, Component::PHP, Component::Caddy, Component::FrankenPHP, Component::MySQL, Component::Redis, Component::Symfony] as $component) {
            self::assertContains($component, $project->getComponents());
        }

        $links = array_values(array_filter($persisted, static fn(object $entity): bool => $entity instanceof ProjectUser));
        self::assertCount(1, $links);
        self::assertSame($owner, $links[0]->getUser());
        self::assertSame($project, $links[0]->getProject());
        self::assertSame(ProjectRole::OWNER, $links[0]->getRole());
    }

    public function testIsANoOpWhenASelfMonitoringProjectAlreadyExists(): void
    {
        $existing = new Project();
        $existing->setUuid(Uuid::fromString(Project::SELF_MONITORING_UUID));

        $repository = $this->createMock(ProjectRepository::class);
        $repository->method('findOneBy')->with(['uuid' => Uuid::fromString(Project::SELF_MONITORING_UUID)])->willReturn($existing);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->never())->method('persist');
        $em->expects($this->never())->method('flush');

        $project = new SelfMonitoringProvisioner($em, $repository)->provision(new User());

        self::assertSame($existing, $project);
    }
}
