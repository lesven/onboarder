<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration: Fügt smtp_port Feld zu email_settings hinzu
 */
final class Version20250801000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Fügt smtp_port Feld zur email_settings Tabelle hinzu';
    }

    public function up(Schema $schema): void
    {
        // Füge smtp_port Feld hinzu
        $this->addSql('ALTER TABLE email_settings ADD smtp_port INT NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // Entferne smtp_port Feld
        $this->addSql('ALTER TABLE email_settings DROP smtp_port');
    }
}
