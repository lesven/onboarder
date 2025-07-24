<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250724134455 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Erstellt OnboardingTask Tabelle für echte Aufgaben (separate von Template-Tasks)';
    }

    public function up(Schema $schema): void
    {
        // OnboardingTask Tabelle erstellen
        $this->addSql('CREATE TABLE onboarding_task (
            id INT AUTO_INCREMENT NOT NULL, 
            onboarding_id INT NOT NULL, 
            assigned_role_id INT DEFAULT NULL, 
            template_task_id INT DEFAULT NULL, 
            task_block_id INT DEFAULT NULL, 
            title VARCHAR(255) NOT NULL, 
            description LONGTEXT DEFAULT NULL, 
            sort_order INT NOT NULL, 
            status VARCHAR(50) NOT NULL, 
            due_date DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', 
            due_days_from_entry INT DEFAULT NULL, 
            assigned_email VARCHAR(255) DEFAULT NULL, 
            send_email TINYINT(1) NOT NULL, 
            email_template LONGTEXT DEFAULT NULL, 
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', 
            updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', 
            completed_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', 
            email_sent_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', 
            INDEX IDX_8B6F8F399395C3F3 (onboarding_id), 
            INDEX IDX_8B6F8F3990BDED9D (assigned_role_id), 
            INDEX IDX_8B6F8F39E9C74025 (template_task_id), 
            INDEX IDX_8B6F8F3939DE1D46 (task_block_id), 
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        
        // OnboardingTask Dependencies Tabelle erstellen
        $this->addSql('CREATE TABLE onboarding_task_dependencies (
            onboarding_task_source INT NOT NULL, 
            onboarding_task_target INT NOT NULL, 
            INDEX IDX_4E8C2A8A5F8A7F73 (onboarding_task_source), 
            INDEX IDX_4E8C2A8A896C3DE4 (onboarding_task_target), 
            PRIMARY KEY(onboarding_task_source, onboarding_task_target)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        
        // Foreign Key Constraints hinzufügen
        $this->addSql('ALTER TABLE onboarding_task ADD CONSTRAINT FK_8B6F8F399395C3F3 FOREIGN KEY (onboarding_id) REFERENCES onboarding (id)');
        $this->addSql('ALTER TABLE onboarding_task ADD CONSTRAINT FK_8B6F8F3990BDED9D FOREIGN KEY (assigned_role_id) REFERENCES role (id)');
        $this->addSql('ALTER TABLE onboarding_task ADD CONSTRAINT FK_8B6F8F39E9C74025 FOREIGN KEY (template_task_id) REFERENCES task (id)');
        $this->addSql('ALTER TABLE onboarding_task ADD CONSTRAINT FK_8B6F8F3939DE1D46 FOREIGN KEY (task_block_id) REFERENCES task_block (id)');
        $this->addSql('ALTER TABLE onboarding_task_dependencies ADD CONSTRAINT FK_4E8C2A8A5F8A7F73 FOREIGN KEY (onboarding_task_source) REFERENCES onboarding_task (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE onboarding_task_dependencies ADD CONSTRAINT FK_4E8C2A8A896C3DE4 FOREIGN KEY (onboarding_task_target) REFERENCES onboarding_task (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // Tabellen und Constraints entfernen
        $this->addSql('ALTER TABLE onboarding_task DROP FOREIGN KEY FK_8B6F8F399395C3F3');
        $this->addSql('ALTER TABLE onboarding_task DROP FOREIGN KEY FK_8B6F8F3990BDED9D');
        $this->addSql('ALTER TABLE onboarding_task DROP FOREIGN KEY FK_8B6F8F39E9C74025');
        $this->addSql('ALTER TABLE onboarding_task DROP FOREIGN KEY FK_8B6F8F3939DE1D46');
        $this->addSql('ALTER TABLE onboarding_task_dependencies DROP FOREIGN KEY FK_4E8C2A8A5F8A7F73');
        $this->addSql('ALTER TABLE onboarding_task_dependencies DROP FOREIGN KEY FK_4E8C2A8A896C3DE4');
        $this->addSql('DROP TABLE onboarding_task');
        $this->addSql('DROP TABLE onboarding_task_dependencies');
    }
}
