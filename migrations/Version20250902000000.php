<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250902000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Expand api_url columns to TEXT for curl commands';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE task MODIFY api_url LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE onboarding_task MODIFY api_url LONGTEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE onboarding_task MODIFY api_url VARCHAR(255) DEFAULT NULL");
        $this->addSql("ALTER TABLE task MODIFY api_url VARCHAR(255) DEFAULT NULL");
    }
}
