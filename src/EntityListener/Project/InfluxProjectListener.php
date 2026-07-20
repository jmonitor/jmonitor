<?php

declare(strict_types=1);

namespace App\EntityListener\Project;

use App\Command\Influx\BucketDeletionCommand;
use App\Console\CommandLauncher;
use App\Entity\Project;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Events;

#[AsEntityListener(event: Events::preRemove, method: 'deleteBucket', entity: Project::class)]
readonly class InfluxProjectListener
{
    public function __construct(
        private CommandLauncher $commandLauncher,
    ) {}

    public function deleteBucket(Project $project): void
    {
        if ($project->getBucketId()) {
            $this->commandLauncher->launchSync([BucketDeletionCommand::NAME, $project->getBucketId()]);
        }
    }
}
