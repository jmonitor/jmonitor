<?php

declare(strict_types=1);

namespace App\Command\Influx;

use App\Bridge\InfluxDb\InfluxDb;
use InfluxDB2\ApiException;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: self::NAME)]
class BucketDeletionCommand
{
    public const string NAME = 'app:influx:delete-bucket';

    public function __construct(private readonly InfluxDb $influxDb) {}

    public function __invoke(SymfonyStyle $io, #[Argument] string $bucketId): int
    {
        try {
            $this->influxDb->deleteBucket($bucketId);
        } catch (ApiException $e) { // TODO throw a dedicated exception; revisit after the InfluxDB 3 upgrade
            if ($e->getCode() === 404) {
                $io->writeln('Bucket not found');

                return Command::SUCCESS;
            }
        }

        $io->writeln('Bucket deleted');

        return Command::SUCCESS;
    }
}
