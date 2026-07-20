<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260707142805 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Initial schema (squash of all previous migrations)';
    }

    public function isTransactional(): bool
    {
        // Pure DDL: MySQL commits each statement implicitly, so the default
        // transactional wrapper breaks on fresh installs (self-hosted first boot
        // runs app:install's admin flush in the same process and inherits a
        // corrupted transaction state).
        return false;
    }

    public function up(Schema $schema): void
    {
        // Databases created before the squash already have the full schema.
        $this->skipIf($schema->hasTable('project'), 'Schema already exists (pre-squash database).');

        $this->addSql('CREATE TABLE alert (id INT AUTO_INCREMENT NOT NULL, uuid BINARY(16) NOT NULL, created_at DATETIME NOT NULL, metric VARCHAR(255) NOT NULL, config JSON DEFAULT NULL, paused TINYINT NOT NULL, project_id INT NOT NULL, UNIQUE INDEX UNIQ_17FD46C1D17F50A6 (uuid), INDEX IDX_17FD46C1166D1F9C (project_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_0900_ai_ci`');
        $this->addSql('CREATE TABLE messenger_processed_messages (run_id INT NOT NULL, attempt SMALLINT NOT NULL, message_type VARCHAR(255) NOT NULL, description VARCHAR(255) DEFAULT NULL, dispatched_at DATETIME NOT NULL, received_at DATETIME NOT NULL, finished_at DATETIME NOT NULL, wait_time BIGINT NOT NULL, handle_time BIGINT NOT NULL, memory_usage BIGINT NOT NULL, transport VARCHAR(255) NOT NULL, tags VARCHAR(255) DEFAULT NULL, failure_type VARCHAR(255) DEFAULT NULL, failure_message LONGTEXT DEFAULT NULL, results JSON DEFAULT NULL, id INT AUTO_INCREMENT NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_0900_ai_ci`');
        $this->addSql('CREATE TABLE `order` (id INT AUTO_INCREMENT NOT NULL, created_at DATETIME NOT NULL, number INT NOT NULL, paid_at DATETIME DEFAULT NULL, total SMALLINT NOT NULL, stripe_customer_id VARCHAR(255) DEFAULT NULL, plan VARCHAR(255) NOT NULL, status VARCHAR(255) NOT NULL, user_id INT DEFAULT NULL, project_id INT DEFAULT NULL, UNIQUE INDEX UNIQ_F529939896901F54 (number), INDEX IDX_F5299398A76ED395 (user_id), INDEX IDX_F5299398166D1F9C (project_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_0900_ai_ci`');
        $this->addSql('CREATE TABLE project (id INT AUTO_INCREMENT NOT NULL, uuid BINARY(16) NOT NULL, created_at DATETIME NOT NULL, name VARCHAR(255) NOT NULL, api_key VARCHAR(32) NOT NULL, bucket_id VARCHAR(64) DEFAULT NULL, bucket_name VARCHAR(13) DEFAULT NULL, components JSON NOT NULL, status VARCHAR(255) NOT NULL, last_data_push_date DATE DEFAULT NULL, subscription_id INT DEFAULT NULL, UNIQUE INDEX UNIQ_2FB3D0EED17F50A6 (uuid), UNIQUE INDEX UNIQ_2FB3D0EEC912ED9D (api_key), UNIQUE INDEX UNIQ_2FB3D0EE84CE584D (bucket_id), UNIQUE INDEX UNIQ_2FB3D0EE9A1887DC (subscription_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_0900_ai_ci`');
        $this->addSql('CREATE TABLE project_invitation (id INT AUTO_INCREMENT NOT NULL, uniquid VARCHAR(16) NOT NULL, created_at DATETIME NOT NULL, email VARCHAR(255) NOT NULL, role VARCHAR(255) NOT NULL, project_id INT NOT NULL, UNIQUE INDEX UNIQ_E9BB1A90FAB94309 (uniquid), INDEX IDX_E9BB1A90166D1F9C (project_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_0900_ai_ci`');
        $this->addSql('CREATE TABLE project_user (id INT AUTO_INCREMENT NOT NULL, created_at DATETIME NOT NULL, role VARCHAR(32) NOT NULL, alert_notifications_enabled TINYINT NOT NULL, user_id INT NOT NULL, project_id INT NOT NULL, INDEX IDX_B4021E51A76ED395 (user_id), INDEX IDX_B4021E51166D1F9C (project_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_0900_ai_ci`');
        $this->addSql('CREATE TABLE subscription (id INT AUTO_INCREMENT NOT NULL, created_at DATETIME NOT NULL, start_at DATE NOT NULL, end_at DATE NOT NULL, stripe_subscription_id VARCHAR(128) DEFAULT NULL, auto_renew TINYINT NOT NULL, plan VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_A3C664D3B5DBB761 (stripe_subscription_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_0900_ai_ci`');
        $this->addSql('CREATE TABLE `user` (id INT AUTO_INCREMENT NOT NULL, uuid BINARY(16) NOT NULL, created_by_ip VARCHAR(39) DEFAULT NULL, created_at DATETIME NOT NULL, email VARCHAR(180) NOT NULL, role VARCHAR(32) NOT NULL, password VARCHAR(255) DEFAULT NULL, password_lost_hash VARCHAR(13) DEFAULT NULL, activation_hash VARCHAR(13) DEFAULT NULL, deleted_at DATETIME DEFAULT NULL, subscribed_to_newsletter TINYINT DEFAULT NULL, subscribed_to_partner_marketing TINYINT DEFAULT NULL, subscribed_to_jmonitor_marketing TINYINT DEFAULT NULL, status VARCHAR(255) NOT NULL, last_connected_date DATE DEFAULT NULL, UNIQUE INDEX UNIQ_8D93D649D17F50A6 (uuid), UNIQUE INDEX UNIQ_8D93D649E7927C74 (email), UNIQUE INDEX UNIQ_8D93D649C9EDE1F4 (password_lost_hash), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_0900_ai_ci`');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 (queue_name, available_at, delivered_at, id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_0900_ai_ci`');
        $this->addSql('ALTER TABLE alert ADD CONSTRAINT FK_17FD46C1166D1F9C FOREIGN KEY (project_id) REFERENCES project (id)');
        $this->addSql('ALTER TABLE `order` ADD CONSTRAINT FK_F5299398A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE `order` ADD CONSTRAINT FK_F5299398166D1F9C FOREIGN KEY (project_id) REFERENCES project (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE project ADD CONSTRAINT FK_2FB3D0EE9A1887DC FOREIGN KEY (subscription_id) REFERENCES subscription (id)');
        $this->addSql('ALTER TABLE project_invitation ADD CONSTRAINT FK_E9BB1A90166D1F9C FOREIGN KEY (project_id) REFERENCES project (id)');
        $this->addSql('ALTER TABLE project_user ADD CONSTRAINT FK_B4021E51A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE project_user ADD CONSTRAINT FK_B4021E51166D1F9C FOREIGN KEY (project_id) REFERENCES project (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE alert DROP FOREIGN KEY FK_17FD46C1166D1F9C');
        $this->addSql('ALTER TABLE `order` DROP FOREIGN KEY FK_F5299398A76ED395');
        $this->addSql('ALTER TABLE `order` DROP FOREIGN KEY FK_F5299398166D1F9C');
        $this->addSql('ALTER TABLE project DROP FOREIGN KEY FK_2FB3D0EE9A1887DC');
        $this->addSql('ALTER TABLE project_invitation DROP FOREIGN KEY FK_E9BB1A90166D1F9C');
        $this->addSql('ALTER TABLE project_user DROP FOREIGN KEY FK_B4021E51A76ED395');
        $this->addSql('ALTER TABLE project_user DROP FOREIGN KEY FK_B4021E51166D1F9C');
        $this->addSql('DROP TABLE alert');
        $this->addSql('DROP TABLE messenger_processed_messages');
        $this->addSql('DROP TABLE `order`');
        $this->addSql('DROP TABLE project');
        $this->addSql('DROP TABLE project_invitation');
        $this->addSql('DROP TABLE project_user');
        $this->addSql('DROP TABLE subscription');
        $this->addSql('DROP TABLE `user`');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
