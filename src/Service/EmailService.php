<?php

namespace App\Service;

use App\Entity\EmailSettings;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mime\Email;

class EmailService
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
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

        $dsn = sprintf('smtp://%s:%s@%s', urlencode((string) $settings->getSmtpUsername()), urlencode((string) $settings->getSmtpPassword()), $settings->getSmtpHost());
        if ($settings->isIgnoreSslCertificate()) {
            $dsn .= '?verify_peer=0';
        }
        $transport = Transport::fromDsn($dsn);
        $mailer = new \Symfony\Component\Mailer\Mailer($transport);

        $email = (new Email())
            ->from($settings->getSmtpUsername() ?: 'test@example.com')
            ->to($recipient)
            ->subject($subject)
            ->text($text);

        $mailer->send($email);
    }
}
