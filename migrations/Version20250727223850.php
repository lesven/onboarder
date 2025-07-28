<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250727223850 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE base_type (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE email_settings (id INT AUTO_INCREMENT NOT NULL, smtp_host VARCHAR(255) NOT NULL, smtp_port INT NOT NULL, smtp_username VARCHAR(255) DEFAULT NULL, smtp_password VARCHAR(255) DEFAULT NULL, ignore_ssl_certificate TINYINT(1) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE onboarding (id INT AUTO_INCREMENT NOT NULL, onboarding_type_id INT NOT NULL, first_name VARCHAR(255) NOT NULL, last_name VARCHAR(255) NOT NULL, entry_date DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', position VARCHAR(255) DEFAULT NULL, team VARCHAR(255) DEFAULT NULL, manager VARCHAR(255) NOT NULL, manager_email VARCHAR(255) NOT NULL, buddy VARCHAR(255) NOT NULL, buddy_email VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_23A7BB0E4841004 (onboarding_type_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE onboarding_task (id INT AUTO_INCREMENT NOT NULL, onboarding_id INT NOT NULL, assigned_role_id INT DEFAULT NULL, template_task_id INT DEFAULT NULL, task_block_id INT DEFAULT NULL, title VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, sort_order INT NOT NULL, status VARCHAR(50) NOT NULL, due_date DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', due_days_from_entry INT DEFAULT NULL, assigned_email VARCHAR(255) DEFAULT NULL, send_email TINYINT(1) NOT NULL, email_template LONGTEXT DEFAULT NULL, action_type VARCHAR(50) NOT NULL, api_url LONGTEXT DEFAULT NULL, completion_token VARCHAR(64) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', completed_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX UNIQ_1886E040DE6117ED (completion_token), INDEX IDX_1886E040235CA921 (onboarding_id), INDEX IDX_1886E040DC9B9A23 (assigned_role_id), INDEX IDX_1886E040DE0E7ABF (template_task_id), INDEX IDX_1886E040A92D6840 (task_block_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE onboarding_task_dependencies (onboarding_task_source INT NOT NULL, onboarding_task_target INT NOT NULL, INDEX IDX_EE61BEA75FFDCC49 (onboarding_task_source), INDEX IDX_EE61BEA746189CC6 (onboarding_task_target), PRIMARY KEY(onboarding_task_source, onboarding_task_target)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE onboarding_type (id INT AUTO_INCREMENT NOT NULL, base_type_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_C6266C4CF14A35F7 (base_type_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE role (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, email VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE task (id INT AUTO_INCREMENT NOT NULL, task_block_id INT NOT NULL, onboarding_id INT DEFAULT NULL, assigned_role_id INT DEFAULT NULL, title VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, sort_order INT NOT NULL, status VARCHAR(50) NOT NULL, due_date DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', due_days_from_entry INT DEFAULT NULL, email_trigger VARCHAR(50) NOT NULL, email_send_date DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', email_delay_days INT DEFAULT NULL, assigned_email VARCHAR(255) DEFAULT NULL, email_template LONGTEXT DEFAULT NULL, action_type VARCHAR(50) NOT NULL, api_url LONGTEXT DEFAULT NULL, has_reminder TINYINT(1) NOT NULL, reminder_send_date DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', reminder_delay_days INT DEFAULT NULL, reminder_template LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', completed_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', reminder_sent_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_527EDB25A92D6840 (task_block_id), INDEX IDX_527EDB25235CA921 (onboarding_id), INDEX IDX_527EDB25DC9B9A23 (assigned_role_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE task_dependencies (task_id INT NOT NULL, depends_on_task_id INT NOT NULL, INDEX IDX_229E54A08DB60186 (task_id), INDEX IDX_229E54A0BBE30936 (depends_on_task_id), PRIMARY KEY(task_id, depends_on_task_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE task_block (id INT AUTO_INCREMENT NOT NULL, base_type_id INT DEFAULT NULL, onboarding_type_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, sort_order INT NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_44BF8C02F14A35F7 (base_type_id), INDEX IDX_44BF8C024841004 (onboarding_type_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE `user` (id INT AUTO_INCREMENT NOT NULL, username VARCHAR(180) NOT NULL, roles JSON NOT NULL COMMENT \'(DC2Type:json)\', password VARCHAR(255) NOT NULL, email VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX UNIQ_8D93D649F85E0677 (username), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', available_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', delivered_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_75EA56E0FB7336F0 (queue_name), INDEX IDX_75EA56E0E3BD61CE (available_at), INDEX IDX_75EA56E016BA31DB (delivered_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE onboarding ADD CONSTRAINT FK_23A7BB0E4841004 FOREIGN KEY (onboarding_type_id) REFERENCES onboarding_type (id)');
        $this->addSql('ALTER TABLE onboarding_task ADD CONSTRAINT FK_1886E040235CA921 FOREIGN KEY (onboarding_id) REFERENCES onboarding (id)');
        $this->addSql('ALTER TABLE onboarding_task ADD CONSTRAINT FK_1886E040DC9B9A23 FOREIGN KEY (assigned_role_id) REFERENCES role (id)');
        $this->addSql('ALTER TABLE onboarding_task ADD CONSTRAINT FK_1886E040DE0E7ABF FOREIGN KEY (template_task_id) REFERENCES task (id)');
        $this->addSql('ALTER TABLE onboarding_task ADD CONSTRAINT FK_1886E040A92D6840 FOREIGN KEY (task_block_id) REFERENCES task_block (id)');
        $this->addSql('ALTER TABLE onboarding_task_dependencies ADD CONSTRAINT FK_EE61BEA75FFDCC49 FOREIGN KEY (onboarding_task_source) REFERENCES onboarding_task (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE onboarding_task_dependencies ADD CONSTRAINT FK_EE61BEA746189CC6 FOREIGN KEY (onboarding_task_target) REFERENCES onboarding_task (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE onboarding_type ADD CONSTRAINT FK_C6266C4CF14A35F7 FOREIGN KEY (base_type_id) REFERENCES base_type (id)');
        $this->addSql('ALTER TABLE task ADD CONSTRAINT FK_527EDB25A92D6840 FOREIGN KEY (task_block_id) REFERENCES task_block (id)');
        $this->addSql('ALTER TABLE task ADD CONSTRAINT FK_527EDB25235CA921 FOREIGN KEY (onboarding_id) REFERENCES onboarding (id)');
        $this->addSql('ALTER TABLE task ADD CONSTRAINT FK_527EDB25DC9B9A23 FOREIGN KEY (assigned_role_id) REFERENCES role (id)');
        $this->addSql('ALTER TABLE task_dependencies ADD CONSTRAINT FK_229E54A08DB60186 FOREIGN KEY (task_id) REFERENCES task (id)');
        $this->addSql('ALTER TABLE task_dependencies ADD CONSTRAINT FK_229E54A0BBE30936 FOREIGN KEY (depends_on_task_id) REFERENCES task (id)');
        $this->addSql('ALTER TABLE task_block ADD CONSTRAINT FK_44BF8C02F14A35F7 FOREIGN KEY (base_type_id) REFERENCES base_type (id)');
        $this->addSql('ALTER TABLE task_block ADD CONSTRAINT FK_44BF8C024841004 FOREIGN KEY (onboarding_type_id) REFERENCES onboarding_type (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE onboarding DROP FOREIGN KEY FK_23A7BB0E4841004');
        $this->addSql('ALTER TABLE onboarding_task DROP FOREIGN KEY FK_1886E040235CA921');
        $this->addSql('ALTER TABLE onboarding_task DROP FOREIGN KEY FK_1886E040DC9B9A23');
        $this->addSql('ALTER TABLE onboarding_task DROP FOREIGN KEY FK_1886E040DE0E7ABF');
        $this->addSql('ALTER TABLE onboarding_task DROP FOREIGN KEY FK_1886E040A92D6840');
        $this->addSql('ALTER TABLE onboarding_task_dependencies DROP FOREIGN KEY FK_EE61BEA75FFDCC49');
        $this->addSql('ALTER TABLE onboarding_task_dependencies DROP FOREIGN KEY FK_EE61BEA746189CC6');
        $this->addSql('ALTER TABLE onboarding_type DROP FOREIGN KEY FK_C6266C4CF14A35F7');
        $this->addSql('ALTER TABLE task DROP FOREIGN KEY FK_527EDB25A92D6840');
        $this->addSql('ALTER TABLE task DROP FOREIGN KEY FK_527EDB25235CA921');
        $this->addSql('ALTER TABLE task DROP FOREIGN KEY FK_527EDB25DC9B9A23');
        $this->addSql('ALTER TABLE task_dependencies DROP FOREIGN KEY FK_229E54A08DB60186');
        $this->addSql('ALTER TABLE task_dependencies DROP FOREIGN KEY FK_229E54A0BBE30936');
        $this->addSql('ALTER TABLE task_block DROP FOREIGN KEY FK_44BF8C02F14A35F7');
        $this->addSql('ALTER TABLE task_block DROP FOREIGN KEY FK_44BF8C024841004');
        $this->addSql('DROP TABLE base_type');
        $this->addSql('DROP TABLE email_settings');
        $this->addSql('DROP TABLE onboarding');
        $this->addSql('DROP TABLE onboarding_task');
        $this->addSql('DROP TABLE onboarding_task_dependencies');
        $this->addSql('DROP TABLE onboarding_type');
        $this->addSql('DROP TABLE role');
        $this->addSql('DROP TABLE task');
        $this->addSql('DROP TABLE task_dependencies');
        $this->addSql('DROP TABLE task_block');
        $this->addSql('DROP TABLE `user`');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
