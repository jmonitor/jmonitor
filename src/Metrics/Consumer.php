<?php

declare(strict_types=1);

namespace App\Metrics;

use App\Bridge\InfluxDb\InfluxDb;
use App\Command\Influx\BucketCreationCommand;
use App\Console\CommandLauncher;
use App\Entity\Project;
use App\Event\PostConsumeEvent;
use App\Metrics\Consumer\Consumer as ConsumerEnum;
use App\Metrics\Consumer\ConsumerInterface;
use App\Metrics\Dto\MetricBagDto;
use App\Security\Voter\Right\Right;
use Doctrine\ORM\EntityManagerInterface;
use InfluxDB2\Model\WritePrecision;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\AutowireLocator;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * "Digests" the metrics
 * (sends them to InfluxDB, to Redis, ...)
 */
readonly class Consumer
{
    public function __construct(
        #[AutowireLocator('app.consumer')]
        private ContainerInterface $consumers,
        private LoggerInterface $logger,
        private InfluxDb $influxDb,
        private MetricsBagProvider $metricsCacheManager,
        private EventDispatcherInterface $eventDispatcher,
        private ValidatorInterface $validator,
        private Security $security,
        private CommandLauncher $commandLauncher,
        private EntityManagerInterface $em,
    ) {}

    public function consume(array $batch, Project $project, \DateTimeImmutable $receivedAt, string $jmonitorVersion, ?string $bundleVersion = null): void
    {
        $inputConstraints = $this->getInputConstraints();
        $bags = [];
        $points = [];

        foreach ($batch as $input) {
            // validation
            $inputErrors = $this->validator->validate($input, $inputConstraints);

            if (count($inputErrors) > 0) {
                $this->logger->error('Invalid consumer input', [
                    'errors' => array_map(fn(ConstraintViolationInterface $error): string => $error->getMessage() . ' - ' . $error->getPropertyPath(), iterator_to_array($inputErrors)),
                    'input' => $input,
                    'project' => $project->getId(),
                    'time' => $receivedAt->getTimestamp(),
                ]);

                continue;
            }

            // normalization
            $input['metrics'] ??= [];
            $input['threw'] ??= false;
            // TODO handle $input['skipped']
            $input['duration'] ??= $input['time'] ?? 0; // older collectors sent 'time' instead of 'duration'

            $consumerEnum = ConsumerEnum::from($input['name']);
            /** @var ConsumerInterface $consumer */
            $consumer = $this->consumers->get($consumerEnum->value);

            // metrics disabled in the project's settings
            if (!$project->hasComponent($consumerEnum->getComponent())) {
                continue;
            }

            // validation
            $errors = $this->validator->validate($input['metrics'], $consumer->getConstraints($input['version']));

            if (count($errors) > 0) {
                $this->logger->error('Invalid metrics', [
                    'errors' => array_map(fn(ConstraintViolationInterface $error): string => $error->getMessage() . ' - ' . $error->getPropertyPath(), iterator_to_array($errors)),
                    'metrics' => $input['metrics'],
                    'consumer' => $consumerEnum->value,
                    'project' => [
                        'id' => $project->getId(),
                        'name' => $project->getName(),
                    ],
                    'time' => $receivedAt->getTimestamp(),
                    'version' => $input['version'],
                ]);

                continue;
            }

            $bag = MetricBagDto::create(
                $project,
                $consumerEnum,
                $input['version'],
                $input['metrics'],
                $receivedAt,
                $input['threw'],
            );

            // normalization (e.g. computing derived metrics)
            $bag = $consumer->normalizeBag($bag);

            // always save a metrics bag, even with no metrics to cache, so we keep a last-update date
            $metricsToCache = $consumer->getMetricsToCache($bag);
            $metricsToCache = array_filter($metricsToCache, fn($value): bool => $value !== null);
            $this->metricsCacheManager->saveMetricBag($bag->withMetrics($metricsToCache), deferred: true);

            foreach ($consumer->getInfluxPoints($bag) as $point) {
                $point->time($bag->getReceivedAt()->getTimestamp(), WritePrecision::S);

                // collect the points to send them in one batch instead of one by one, saves API calls
                $points[] = $point;
            }

            // and keep the bag for PostConsumeEvent
            $bags[$bag->getConsumer()->value] = $bag;
        }

        // cache everything at once
        $this->metricsCacheManager->commit();

        // and send the InfluxDB data
        if ($points && $this->security->isGranted(Right::TIME_SERIES_CHART->value, $project)) {
            if (!$project->getBucketId()) {
                $this->commandLauncher->launchSync([BucketCreationCommand::COMMAND, $project->getId()]);
                $this->em->refresh($project);
            }

            $this->influxDb->writePoints($points, $project->getBucketId());
        }

        // dispatch event
        $this->eventDispatcher->dispatch(new PostConsumeEvent($project, $bags, $receivedAt, $jmonitorVersion, $bundleVersion));
    }

    private function getInputConstraints(): Assert\Collection
    {
        return new Assert\Collection(
            fields: [
                'version' => new Assert\Required(
                    // all collectors are at version 1 for now
                    constraints: new Assert\IdenticalTo(1),
                ),
                'metrics' => new Assert\Optional(
                    constraints: new Assert\Type('array'),
                ),
                'name' => new Assert\Required(
                    constraints: [
                        new Assert\Type('string'),
                        new Assert\Choice(choices: ConsumerEnum::values()),
                    ],
                ),
                // @deprecated, replaced by 'duration', kept while older collectors are still deployed
                'time' => new Assert\Optional(
                    constraints: [
                        new Assert\Type('float'),
                        new Assert\GreaterThan(0),
                    ],
                ),
                'duration' => new Assert\Optional(
                    constraints: [
                        new Assert\Type('float'),
                        new Assert\GreaterThan(0),
                    ],
                ),
                // collectors only send 'threw' and 'skipped' when they are true
                'threw' => new Assert\Optional(
                    constraints: new Assert\Type('bool'),
                ),
                'skipped' => new Assert\Optional(
                    constraints: new Assert\Type('bool'),
                ),
            ],
            allowExtraFields: false,
            allowMissingFields: false,
        );
    }
}
