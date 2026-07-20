<?php

declare(strict_types=1);

namespace App\Install;

use App\Entity\Enums\Component;
use App\Entity\Enums\ProjectRole;
use App\Entity\Project;
use App\Entity\ProjectUser;
use App\Entity\User;
use App\Repository\ProjectRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

/**
 * Creates the self-monitoring project on the first boot of a self-hosted
 * instance (app:install), identified by Project::SELF_MONITORING_UUID (the
 * name is editable). Regular project owned by the initial admin — no
 * subscription (PlanResolver grants SELF_HOSTED) and no InfluxDB bucket
 * (created on the first push).
 */
readonly class SelfMonitoringProvisioner
{
    public const string PROJECT_NAME = 'jmonitor';

    /**
     * The self-hosted stack (docker/selfhosted/compose.yaml): everything the
     * collector service actually collects.
     */
    private const array COMPONENTS = [
        Component::System,
        Component::PHP,
        Component::Caddy,
        Component::FrankenPHP,
        Component::MySQL,
        Component::Redis,
        Component::Symfony,
    ];

    public function __construct(
        private EntityManagerInterface $em,
        private ProjectRepository $projectRepository,
    ) {}

    /**
     * @return Project the created (or already existing) self-monitoring project
     */
    public function provision(User $owner): Project
    {
        $existing = $this->projectRepository->findOneBy(['uuid' => Uuid::fromString(Project::SELF_MONITORING_UUID)]);
        if ($existing !== null) {
            return $existing;
        }

        $project = new Project();
        $project->setName(self::PROJECT_NAME);
        $project->setComponents(self::COMPONENTS);
        $project->setUuid(Uuid::fromString(Project::SELF_MONITORING_UUID));

        $link = new ProjectUser();
        $link->setUser($owner);
        $link->setProject($project);
        $link->setRole(ProjectRole::OWNER);

        $this->em->persist($project);
        $this->em->persist($link);
        $this->em->flush();

        return $project;
    }
}
