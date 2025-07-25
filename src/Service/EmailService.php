<?php

namespace App\Service;

use App\Entity\EmailSettings;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mime\Email;

class EmailService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly PasswordEncryptionService $encryptionService
    ) {
    }

    /**
     * Sends a simple text email using the stored settings.
     */
    public function sendTestMail(string $recipient, string $subject, string $text): void
    {
        $settings = $this->entityManager->getRepository(EmailSettings::class)->findOneBy([]);
        if (!$settings) {
            throw new \RuntimeException('Keine E-Mail-Einstellungen gefunden.');
        }

        // Setze den Verschlüsselungsservice in die Entity
        $settings->setEncryptionService($this->encryptionService);

        // Baue DSN basierend auf verfügbaren Credentials
        if ($settings->getSmtpUsername() && $settings->getSmtpPassword()) {
            // Mit Authentifizierung
            $dsn = sprintf('smtp://%s:%s@%s:%d',
                urlencode((string) $settings->getSmtpUsername()),
                urlencode((string) $settings->getSmtpPassword()),
                $settings->getSmtpHost(),
                $settings->getSmtpPort()
            );
        } else {
            // Ohne Authentifizierung
            $dsn = sprintf('smtp://%s:%d', $settings->getSmtpHost(), $settings->getSmtpPort());
        }
        
        if ($settings->isIgnoreSslCertificate()) {
            $dsn .= '?verify_peer=0';
        }
        $transport = Transport::fromDsn($dsn);
        $mailer = new \Symfony\Component\Mailer\Mailer($transport);

        $email = (new Email())
            ->from($settings->getSmtpUsername() ?: 'example@example.com')
            ->to($recipient)
            ->subject($subject)
            ->text($text);

        $mailer->send($email);
    }
}
