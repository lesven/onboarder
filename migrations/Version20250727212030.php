<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250727212030 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE onboarding_task DROP FOREIGN KEY FK_1886E040DE0E7ABF');
        $this->addSql('ALTER TABLE onboarding_task ADD CONSTRAINT FK_1886E040DE0E7ABF FOREIGN KEY (template_task_id) REFERENCES task (taskId)');
        $this->addSql('ALTER TABLE task MODIFY id INT NOT NULL');
        $this->addSql('DROP INDEX `primary` ON task');
        $this->addSql('ALTER TABLE task ADD email_delay_days INT DEFAULT NULL, ADD reminder_delay_days INT DEFAULT NULL, DROP email_send_days_from_entry, DROP reminder_send_days_from_entry, DROP email_sent_at, CHANGE id task_id INT AUTO_INCREMENT NOT NULL');
        $this->addSql('ALTER TABLE task ADD PRIMARY KEY (task_id)');
        $this->addSql('ALTER TABLE task_dependencies DROP FOREIGN KEY FK_229E54A07DC6AB2F');
        $this->addSql('ALTER TABLE task_dependencies DROP FOREIGN KEY FK_229E54A06423FBA0');
        $this->addSql('DROP INDEX IDX_229E54A06423FBA0 ON task_dependencies');
        $this->addSql('DROP INDEX IDX_229E54A07DC6AB2F ON task_dependencies');
        $this->addSql('DROP INDEX `primary` ON task_dependencies');
        $this->addSql('ALTER TABLE task_dependencies ADD task_id INT NOT NULL, ADD depends_on_task_id INT NOT NULL, DROP task_source, DROP task_target');
        $this->addSql('ALTER TABLE task_dependencies ADD CONSTRAINT FK_229E54A08DB60186 FOREIGN KEY (task_id) REFERENCES task (taskId)');
        $this->addSql('ALTER TABLE task_dependencies ADD CONSTRAINT FK_229E54A0BBE30936 FOREIGN KEY (depends_on_task_id) REFERENCES task (taskId)');
        $this->addSql('CREATE INDEX IDX_229E54A08DB60186 ON task_dependencies (task_id)');
        $this->addSql('CREATE INDEX IDX_229E54A0BBE30936 ON task_dependencies (depends_on_task_id)');
        $this->addSql('ALTER TABLE task_dependencies ADD PRIMARY KEY (task_id, depends_on_task_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE task MODIFY task_id INT NOT NULL');
        $this->addSql('DROP INDEX `PRIMARY` ON task');
        $this->addSql('ALTER TABLE task ADD email_send_days_from_entry INT DEFAULT NULL, ADD reminder_send_days_from_entry INT DEFAULT NULL, ADD email_sent_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', DROP email_delay_days, DROP reminder_delay_days, CHANGE task_id id INT AUTO_INCREMENT NOT NULL');
        $this->addSql('ALTER TABLE task ADD PRIMARY KEY (id)');
        $this->addSql('ALTER TABLE task_dependencies DROP FOREIGN KEY FK_229E54A08DB60186');
        $this->addSql('ALTER TABLE task_dependencies DROP FOREIGN KEY FK_229E54A0BBE30936');
        $this->addSql('DROP INDEX IDX_229E54A08DB60186 ON task_dependencies');
        $this->addSql('DROP INDEX IDX_229E54A0BBE30936 ON task_dependencies');
        $this->addSql('DROP INDEX `PRIMARY` ON task_dependencies');
        $this->addSql('ALTER TABLE task_dependencies ADD task_source INT NOT NULL, ADD task_target INT NOT NULL, DROP task_id, DROP depends_on_task_id');
        $this->addSql('ALTER TABLE task_dependencies ADD CONSTRAINT FK_229E54A07DC6AB2F FOREIGN KEY (task_target) REFERENCES task (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE task_dependencies ADD CONSTRAINT FK_229E54A06423FBA0 FOREIGN KEY (task_source) REFERENCES task (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_229E54A06423FBA0 ON task_dependencies (task_source)');
        $this->addSql('CREATE INDEX IDX_229E54A07DC6AB2F ON task_dependencies (task_target)');
        $this->addSql('ALTER TABLE task_dependencies ADD PRIMARY KEY (task_source, task_target)');
        $this->addSql('ALTER TABLE onboarding_task DROP FOREIGN KEY FK_1886E040DE0E7ABF');
        $this->addSql('ALTER TABLE onboarding_task ADD CONSTRAINT FK_1886E040DE0E7ABF FOREIGN KEY (template_task_id) REFERENCES task (id)');
    }
}
