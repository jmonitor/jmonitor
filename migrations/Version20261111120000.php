<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261111120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Delete the embeds of the Cloudflare cards that no longer exist';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            DELETE FROM embed
            WHERE JSON_UNQUOTE(JSON_EXTRACT(config, '$.m')) IN (
                'cloudflare.bandwidth',
                'cloudflare.origin_requests',
                'cloudflare.by_protocol',
                'cloudflare.by_content_type',
                'cloudflare.cache_settings'
            )
            SQL);
    }

    public function down(Schema $schema): void {}

    public function isTransactional(): bool
    {
        return false;
    }
}
