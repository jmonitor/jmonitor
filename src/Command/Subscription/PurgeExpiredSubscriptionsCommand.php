<?php

declare(strict_types=1);

namespace App\Command\Subscription;

use App\Bridge\InfluxDb\InfluxDb;
use App\Entity\Enums\Plan;
use App\Plan\Edition;
use App\Plan\PlanResolver;
use App\Repository\SubscriptionRepository;
use Doctrine\ORM\EntityManagerInterface;
use InfluxDB2\ApiException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Scheduler\Attribute\AsPeriodicTask;

/**
 * Purges subscriptions that have been expired for longer than the grace period:
 * deletes the Influx bucket (data) then the subscription in the database.
 * Covers both expired Stripe subscriptions and trials (without Stripe).
 */
#[AsCommand(self::NAME, 'Purge expired subscriptions (bucket + subscription) after the grace period')]
#[AsPeriodicTask('24 hours', from: '04:30', jitter: 60)]
class PurgeExpiredSubscriptionsCommand
{
    public const string NAME = 'app:subscription:purge-expired';

    /**
     * Delay after expiration before the data is permanently deleted.
     */
    public const string GRACE_PERIOD = '-7 days';

    public function __construct(
        private readonly InfluxDb $influxDb,
        private readonly SubscriptionRepository $subscriptionRepository,
        private readonly EntityManagerInterface $em,
        private readonly LoggerInterface $logger,
        private readonly PlanResolver $planResolver,
        private readonly Edition $edition,
    ) {}

    public function __invoke(SymfonyStyle $io): int
    {
        // Cloud-only command. Already a no-op in self-hosted (the resolved plan
        // is never FREE); the early return saves the scan and makes the intent explicit.
        if ($this->edition->isSelfHosted()) {
            $io->info('Cloud-only command: nothing to purge in the self-hosted edition.');

            return Command::SUCCESS;
        }

        $expiredBefore = new \DateTimeImmutable(self::GRACE_PERIOD);
        $subscriptions = $this->subscriptionRepository->findExpiredBefore($expiredBefore);

        $purged = 0;

        foreach ($subscriptions as $subscription) {
            $project = $subscription->getProject();

            // if the project re-subscribed in the meantime, leave everything untouched (data safety).
            if ($project && $this->planResolver->resolve($project) !== Plan::FREE) {
                continue;
            }

            // if the bucket deletion fails, keep the subscription to retry
            // on the next run (otherwise we would end up with an orphan bucket without any reference).
            if ($project && $project->getBucketId()) {
                if (!$this->deleteBucket($project->getBucketId(), $project->getId())) {
                    continue;
                }

                $project->setBucketId(null);
                $project->setBucketName(null);

                $io->writeln(sprintf('Bucket deleted for project %d', $project->getId()));
            }

            if ($project) {
                $project->setSubscription(null);
            }

            $this->em->remove($subscription);
            ++$purged;
        }

        $this->em->flush();

        $io->success(sprintf('%d expired subscription(s) purged', $purged));

        return Command::SUCCESS;
    }

    private function deleteBucket(string $bucketId, ?int $projectId): bool
    {
        try {
            $this->influxDb->deleteBucket($bucketId);
        } catch (ApiException $e) {
            if ($e->getCode() !== 404) {
                $this->logger->error('Unable to delete expired bucket', [
                    'projectId' => $projectId,
                    'bucketId' => $bucketId,
                    'exception' => $e,
                ]);

                return false;
            }
        }

        return true;
    }
}
