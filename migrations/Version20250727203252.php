<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250727203252 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE onboarding_task CHANGE api_url api_url LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE task CHANGE action_type action_type VARCHAR(50) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE task CHANGE action_type action_type VARCHAR(50) DEFAULT \'none\' NOT NULL');
        $this->addSql('ALTER TABLE onboarding_task CHANGE api_url api_url VARCHAR(255) DEFAULT NULL');
    }
}
