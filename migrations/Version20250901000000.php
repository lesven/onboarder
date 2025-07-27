<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250901000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Adds action type and api url to tasks';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE task ADD action_type VARCHAR(50) NOT NULL DEFAULT 'none', ADD api_url VARCHAR(255) DEFAULT NULL");
        $this->addSql("UPDATE task SET action_type = 'email' WHERE email_template IS NOT NULL");
        $this->addSql("ALTER TABLE onboarding_task ADD action_type VARCHAR(50) NOT NULL DEFAULT 'none', ADD api_url VARCHAR(255) DEFAULT NULL");
        $this->addSql("UPDATE onboarding_task SET action_type = 'email' WHERE send_email = 1");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE onboarding_task DROP action_type, DROP api_url');
        $this->addSql('ALTER TABLE task DROP action_type, DROP api_url');
    }
}
