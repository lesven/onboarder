<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250802000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Make manager and buddy related fields mandatory';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE onboarding SET manager = '' WHERE manager IS NULL");
        $this->addSql("UPDATE onboarding SET manager_email = '' WHERE manager_email IS NULL");
        $this->addSql("UPDATE onboarding SET buddy = '' WHERE buddy IS NULL");
        $this->addSql("UPDATE onboarding SET buddy_email = '' WHERE buddy_email IS NULL");

        $this->addSql('ALTER TABLE onboarding CHANGE manager manager VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE onboarding CHANGE manager_email manager_email VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE onboarding CHANGE buddy buddy VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE onboarding CHANGE buddy_email buddy_email VARCHAR(255) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE onboarding CHANGE manager manager VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE onboarding CHANGE manager_email manager_email VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE onboarding CHANGE buddy buddy VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE onboarding CHANGE buddy_email buddy_email VARCHAR(255) DEFAULT NULL');
    }
}
