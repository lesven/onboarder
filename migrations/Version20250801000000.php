<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250801000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Erstellt Tabelle für E-Mail-Einstellungen';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE email_settings (id INT AUTO_INCREMENT NOT NULL, smtp_host VARCHAR(255) NOT NULL, smtp_username VARCHAR(255) DEFAULT NULL, smtp_password VARCHAR(255) DEFAULT NULL, ignore_ssl_certificate TINYINT(1) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE email_settings');
    }
}
