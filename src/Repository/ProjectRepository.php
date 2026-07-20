<?php

namespace App\Repository;

use App\Entity\Project;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Project>
 */
class ProjectRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Project::class);
    }

    /**
     * The public demo project: the project named $name owned by the demo user $email.
     */
    public function findDemoProject(string $email, string $name): ?Project
    {
        return $this->createQueryBuilder('p')
            ->join('p.projectUsers', 'pu')
            ->join('pu.user', 'u')
            ->andWhere('u.email = :email')
            ->andWhere('p.name = :name')
            ->setParameter('email', $email)
            ->setParameter('name', $name)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
