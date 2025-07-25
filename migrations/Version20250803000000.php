<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250803000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Adds completion token to onboarding_task';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE onboarding_task ADD completion_token VARCHAR(64) NOT NULL");
        $this->addSql("UPDATE onboarding_task SET completion_token = SUBSTRING(MD5(RAND()),1,32)");
        $this->addSql("ALTER TABLE onboarding_task ADD UNIQUE INDEX UNIQ_FCE27CE3CDE9BDAB (completion_token)");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE onboarding_task DROP INDEX UNIQ_FCE27CE3CDE9BDAB');
        $this->addSql('ALTER TABLE onboarding_task DROP completion_token');
    }
}
