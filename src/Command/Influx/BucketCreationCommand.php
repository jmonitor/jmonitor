<?php

declare(strict_types=1);

namespace App\Command\Influx;

use App\Bridge\InfluxDb\InfluxDb;
use App\Repository\ProjectRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: self::COMMAND)]
class BucketCreationCommand
{
    public const string COMMAND = 'app:influx:create-bucket';

    public function __construct(
        private readonly InfluxDb $influxDb,
        private readonly ProjectRepository $projectRepository,
        private readonly EntityManagerInterface $em,
    ) {}

    public function __invoke(SymfonyStyle $io, #[Argument] string $projectId): int
    {
        $project = $this->projectRepository->find($projectId);

        if (!$project) {
            $io->writeln('Project not found');

            return Command::FAILURE;
        }

        if ($project->getBucketId()) {
            $io->writeln('Bucket already created');

            return Command::SUCCESS;
        }

        $bucket = $this->influxDb->createBucketForProject($project);
        $project->setBucketId($bucket->getId());
        $project->setBucketName($bucket->getName());
        $this->em->flush();

        $io->writeln('Bucket created and associated');

        return Command::SUCCESS;
    }
}
