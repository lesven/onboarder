<?php

namespace App\Service;

use App\Entity\EmailSettings;
use App\Entity\OnboardingTask;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class EmailService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly PasswordEncryptionService $encryptionService,
        private readonly UrlGeneratorInterface $urlGenerator
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

    /**
     * Renders an email template with onboarding specific placeholders.
     */
    public function renderTemplate(string $template, OnboardingTask $task): string
    {
        $onboarding = $task->getOnboarding();

        $placeholders = [
            '{{firstName}}'    => $onboarding?->getFirstName() ?? '',
            '{{lastName}}'     => $onboarding?->getLastName() ?? '',
            '{{entryDate}}'    => $onboarding?->getEntryDate()?->format('Y-m-d') ?? '',
            '{{onboardingId}}' => (string)($onboarding?->getId() ?? ''),
            '{{taskId}}'       => (string)($task->getId() ?? ''),
            '{{manager}}'      => $onboarding?->getManager() ?? '',
            '{{managerEmail}}'      => $onboarding?->getManagerEmail() ?? '',
            '{{buddy}}'        => $onboarding?->getBuddy() ?? '',
            '{{buddyEmail}}'  => $onboarding?->getBuddyEmail() ?? '',
            '{{onboardingLink}}' => $onboarding ? $this->urlGenerator->generate(
                'app_onboarding_detail',
                ['id' => $onboarding->getId()],
                UrlGeneratorInterface::ABSOLUTE_URL
            ) : '',
        ];

        return strtr($template, $placeholders);
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
