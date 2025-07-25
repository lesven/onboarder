<?php

namespace App\Command;

use App\Entity\EmailSettings;
use App\Service\PasswordEncryptionService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:encrypt-smtp-passwords',
    description: 'Verschlüsselt alle vorhandenen SMTP-Passwörter in der Datenbank'
)]
class EncryptSmtpPasswordsCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly PasswordEncryptionService $encryptionService
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('SMTP-Passwort Verschlüsselung');

        // Hole alle EmailSettings direkt aus der Datenbank (ohne Entity-Encryption)
        $connection = $this->entityManager->getConnection();
        $sql = 'SELECT id, smtp_password FROM email_settings WHERE smtp_password IS NOT NULL AND smtp_password != ""';
        $result = $connection->executeQuery($sql);
        $rows = $result->fetchAllAssociative();

        if (empty($rows)) {
            $io->success('Keine SMTP-Passwörter zum Verschlüsseln gefunden.');
            return Command::SUCCESS;
        }

        $encryptedCount = 0;
        $skippedCount = 0;

        foreach ($rows as $row) {
            $currentPassword = $row['smtp_password'];
            
            // Prüfe ob bereits verschlüsselt
            if ($this->encryptionService->isEncrypted($currentPassword)) {
                $skippedCount++;
                continue;
            }

            // Verschlüssele das Passwort
            $encryptedPassword = $this->encryptionService->encrypt($currentPassword);
            
            // Aktualisiere direkt in der Datenbank
            $updateSql = 'UPDATE email_settings SET smtp_password = ? WHERE id = ?';
            $connection->executeStatement($updateSql, [$encryptedPassword, $row['id']]);
            
            $encryptedCount++;
        }

        $io->success(sprintf(
            'Verschlüsselung abgeschlossen: %d Passwörter verschlüsselt, %d bereits verschlüsselt (übersprungen)',
            $encryptedCount,
            $skippedCount
        ));

        return Command::SUCCESS;
    }
}