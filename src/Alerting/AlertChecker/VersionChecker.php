<?php

declare(strict_types=1);

namespace App\Alerting\AlertChecker;

use App\Alerting\AlertMetric;
use App\Alerting\Config\OutdatedVersionConfig;
use App\Alerting\Dto\SpottedAlert;
use App\Bridge\Eol\EndOfLife;
use App\Entity\Alert;
use App\Entity\Enums\AlertType;
use App\Metrics\Dto\Bag\Apache\ApacheBag;
use App\Metrics\Dto\Bag\Caddy\CaddyBag;
use App\Metrics\Dto\Bag\Mysql\MysqlVariableBag;
use App\Metrics\Dto\Bag\Nginx\NginxBag;
use App\Metrics\Dto\Bag\Php\PhpBag;
use App\Metrics\Dto\Bag\Redis\RedisBag;
use App\Metrics\Dto\Bag\Symfony\SymfonyBag;
use App\Metrics\Dto\MetricBagDto;

class VersionChecker implements AlertCheckerInterface
{
    private EndOfLife $endOfLife;

    public function __construct(EndOfLife $endOfLife)
    {
        $this->endOfLife = $endOfLife;
    }

    public function support(Alert $alert, MetricBagDto $metricBag): bool
    {
        return $alert->getAlertMetric()->getType() === AlertType::Version;
    }

    public function check(Alert $alert, MetricBagDto $metricBag): ?SpottedAlert
    {
        $value = match (get_class($metricBag)) {
            ApacheBag::class => match ($alert->getAlertMetric()) {
                AlertMetric::ApacheVersion => $metricBag->apacheVersionNumber,
                default => throw new \InvalidArgumentException('This code should not be reached'),
            },
            NginxBag::class => match ($alert->getAlertMetric()) {
                AlertMetric::NginxVersion => $metricBag->nginxVersion,
                default => throw new \InvalidArgumentException('This code should not be reached'),
            },
            CaddyBag::class => match ($alert->getAlertMetric()) {
                AlertMetric::CaddyVersion => $metricBag->caddyVersion,
                default => throw new \InvalidArgumentException('This code should not be reached'),
            },
            PhpBag::class => match ($alert->getAlertMetric()) {
                AlertMetric::PhpVersion => $metricBag->phpVersion,
                default => throw new \InvalidArgumentException('This code should not be reached'),
            },
            SymfonyBag::class => match ($alert->getAlertMetric()) {
                AlertMetric::SymfonyVersion => $metricBag->symfonyVersion,
                default => throw new \InvalidArgumentException('This code should not be reached'),
            },
            MysqlVariableBag::class => match ($alert->getAlertMetric()) {
                AlertMetric::MysqlVersion => $metricBag->mysqlVersion,
                default => throw new \InvalidArgumentException('This code should not be reached'),
            },
            RedisBag::class => match ($alert->getAlertMetric()) {
                AlertMetric::RedisVersion => $metricBag->server->version,
                default => throw new \InvalidArgumentException('This code should not be reached'),
            },
            default => null,
        };

        if ($value === null) {
            return null;
        }

        $nbPartsInVersion = $alert->getAlertMetric() === AlertMetric::CaddyVersion ? 3 : 2;
        $cycle = $this->endOfLife->getCycle($value, $alert->getAlertMetric()->component()->eolUrl(), $nbPartsInVersion);

        $config = $alert->getConfig();
        assert($config instanceof OutdatedVersionConfig);

        // no cycle found (e.g. endoflife.date unavailable or unknown version): can't decide, skip
        if (!$cycle) {
            return null;
        }

        return $config->isSatisfiedBy($cycle)
            ? new SpottedAlert($alert)
            : null;
    }
}
