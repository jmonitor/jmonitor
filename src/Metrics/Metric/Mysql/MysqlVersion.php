<?php

declare(strict_types=1);

namespace App\Metrics\Metric\Mysql;

use App\Bridge\Eol\EndOfLife;
use App\Metrics\BagAwareTrait;
use App\Metrics\Metric;
use App\Metrics\Metric\BasicMetricInterface;
use App\Metrics\Renderer\Dto\BasicDto;
use App\Metrics\Renderer\Model\Badge\Badge;
use App\Metrics\Renderer\Model\Badge\BadgeStyle;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

#[AsTaggedItem(Metric::MysqlVersion->value)]
class MysqlVersion implements BasicMetricInterface
{
    use BagAwareTrait;

    private EndOfLife $endOfLife;

    public function __construct(EndOfLife $endOfLife)
    {
        $this->endOfLife = $endOfLife;
    }

    public function getMetric(): Metric
    {
        return Metric::MysqlVersion;
    }

    public function configureBasicDto(BasicDto $dto, array $options = []): void
    {
        $dto
            ->setValueAvailable($this->getMysqlVariablesBag()->mysqlVersion !== null)
            ->setValue($this->getMysqlVariablesBag())
        ;

        if (!$dto->valueAvailable) {
            return;
        }

        $dto->setBadge($this->getBadge());
    }

    private function getBadge(): ?Badge
    {
        $bag = $this->getMysqlVariablesBag();

        if (!$bag->versionComment) {
            return null;
        }

        $url = match (true) {
            str_contains(mb_strtolower($bag->versionComment), 'mariadb') => 'https://endoflife.date/api/mariadb.json',
            str_contains(mb_strtolower($bag->versionComment), 'mysql') => 'https://endoflife.date/api/mysql.json',
            default => null,
        };

        if (!$url) {
            return null;
        }

        $cycle = $this->endOfLife->getCycle($bag->mysqlVersion, $url);

        if (!$cycle) {
            return null;
        }

        return match (true) {
            $cycle->isActive() => new Badge(BadgeStyle::SUCCESS, 'Active support'),
            $cycle->isSecurityFixOnly() => new Badge(BadgeStyle::WARNING, 'Support ending'),
            $cycle->isEol() => new Badge(BadgeStyle::DANGER, 'End of life'),
            default => null,
        };
    }
}
