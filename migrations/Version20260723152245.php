<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260723152245 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function isTransactional(): bool
    {
        return false;
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE embed (id INT AUTO_INCREMENT NOT NULL, token VARCHAR(32) NOT NULL, config JSON NOT NULL, created_at DATETIME NOT NULL, revoked_at DATETIME DEFAULT NULL, last_used_at DATETIME DEFAULT NULL, project_id INT NOT NULL, created_by_id INT DEFAULT NULL, UNIQUE INDEX UNIQ_47BA05135F37A13B (token), INDEX IDX_47BA0513166D1F9C (project_id), INDEX IDX_47BA0513B03A8386 (created_by_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_0900_ai_ci`');
        $this->addSql('ALTER TABLE embed ADD CONSTRAINT FK_47BA0513166D1F9C FOREIGN KEY (project_id) REFERENCES project (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE embed ADD CONSTRAINT FK_47BA0513B03A8386 FOREIGN KEY (created_by_id) REFERENCES `user` (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE embed DROP FOREIGN KEY FK_47BA0513166D1F9C');
        $this->addSql('ALTER TABLE embed DROP FOREIGN KEY FK_47BA0513B03A8386');
        $this->addSql('DROP TABLE embed');
    }
}
