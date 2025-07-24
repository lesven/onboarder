<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250724134429 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE onboarding_task (id INT AUTO_INCREMENT NOT NULL, onboarding_id INT NOT NULL, assigned_role_id INT DEFAULT NULL, template_task_id INT DEFAULT NULL, task_block_id INT DEFAULT NULL, title VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, sort_order INT NOT NULL, status VARCHAR(50) NOT NULL, due_date DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', due_days_from_entry INT DEFAULT NULL, assigned_email VARCHAR(255) DEFAULT NULL, send_email TINYINT(1) NOT NULL, email_template LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', completed_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', email_sent_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_1886E040235CA921 (onboarding_id), INDEX IDX_1886E040DC9B9A23 (assigned_role_id), INDEX IDX_1886E040DE0E7ABF (template_task_id), INDEX IDX_1886E040A92D6840 (task_block_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE onboarding_task_dependencies (onboarding_task_source INT NOT NULL, onboarding_task_target INT NOT NULL, INDEX IDX_EE61BEA75FFDCC49 (onboarding_task_source), INDEX IDX_EE61BEA746189CC6 (onboarding_task_target), PRIMARY KEY(onboarding_task_source, onboarding_task_target)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE onboarding_task ADD CONSTRAINT FK_1886E040235CA921 FOREIGN KEY (onboarding_id) REFERENCES onboarding (id)');
        $this->addSql('ALTER TABLE onboarding_task ADD CONSTRAINT FK_1886E040DC9B9A23 FOREIGN KEY (assigned_role_id) REFERENCES role (id)');
        $this->addSql('ALTER TABLE onboarding_task ADD CONSTRAINT FK_1886E040DE0E7ABF FOREIGN KEY (template_task_id) REFERENCES task (id)');
        $this->addSql('ALTER TABLE onboarding_task ADD CONSTRAINT FK_1886E040A92D6840 FOREIGN KEY (task_block_id) REFERENCES task_block (id)');
        $this->addSql('ALTER TABLE onboarding_task_dependencies ADD CONSTRAINT FK_EE61BEA75FFDCC49 FOREIGN KEY (onboarding_task_source) REFERENCES onboarding_task (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE onboarding_task_dependencies ADD CONSTRAINT FK_EE61BEA746189CC6 FOREIGN KEY (onboarding_task_target) REFERENCES onboarding_task (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE onboarding_task DROP FOREIGN KEY FK_1886E040235CA921');
        $this->addSql('ALTER TABLE onboarding_task DROP FOREIGN KEY FK_1886E040DC9B9A23');
        $this->addSql('ALTER TABLE onboarding_task DROP FOREIGN KEY FK_1886E040DE0E7ABF');
        $this->addSql('ALTER TABLE onboarding_task DROP FOREIGN KEY FK_1886E040A92D6840');
        $this->addSql('ALTER TABLE onboarding_task_dependencies DROP FOREIGN KEY FK_EE61BEA75FFDCC49');
        $this->addSql('ALTER TABLE onboarding_task_dependencies DROP FOREIGN KEY FK_EE61BEA746189CC6');
        $this->addSql('DROP TABLE onboarding_task');
        $this->addSql('DROP TABLE onboarding_task_dependencies');
    }
}
