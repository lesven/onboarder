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
        $this->sendEmailInternal($recipient, $subject, $text, false);
    }

    /**
     * Sends an HTML email using the stored settings.
     */
    public function sendEmail(string $recipient, string $subject, string $html): void
    {
        $this->sendEmailInternal($recipient, $subject, $html, true);
    }

    private function sendEmailInternal(string $recipient, string $subject, string $content, bool $isHtml): void
    {
        $settings = $this->entityManager->getRepository(EmailSettings::class)->findOneBy([]);
        if (!$settings) {
            throw new \RuntimeException('Keine E-Mail-Einstellungen gefunden.');
        }

        $settings->setEncryptionService($this->encryptionService);

        if ($settings->getSmtpUsername() && $settings->getSmtpPassword()) {
            $dsn = sprintf('smtp://%s:%s@%s:%d',
                urlencode((string) $settings->getSmtpUsername()),
                urlencode((string) $settings->getSmtpPassword()),
                $settings->getSmtpHost(),
                $settings->getSmtpPort()
            );
        } else {
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
            ->subject($subject);

        if ($isHtml) {
            $email->html($content);
        } else {
            $email->text($content);
        }

        $mailer->send($email);
    }
}
