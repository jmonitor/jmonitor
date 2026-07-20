<?php

namespace App\Repository;

use App\Entity\Subscription;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Subscription>
 */
class SubscriptionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Subscription::class);
    }

    /**
     * Subscriptions expired before the given date (trials or Stripe).
     * Used to purge expired plans after the grace period (bucket + subscription).
     *
     * @return Subscription[]
     */
    public function findExpiredBefore(\DateTimeImmutable $date): array
    {
        return $this->createQueryBuilder('s')
            ->leftJoin('s.project', 'p')->addSelect('p')
            ->andWhere('s.endAt < :date')
            ->setParameter('date', $date)
            ->getQuery()
            ->getResult();
    }
}
