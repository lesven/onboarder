<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250727184501 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE onboarding_task DROP email_sent_at');
        $this->addSql('ALTER TABLE onboarding_task RENAME INDEX uniq_fce27ce3cde9bdab TO UNIQ_1886E040DE6117ED');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE onboarding_task ADD email_sent_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE onboarding_task RENAME INDEX uniq_1886e040de6117ed TO UNIQ_FCE27CE3CDE9BDAB');
    }
}
