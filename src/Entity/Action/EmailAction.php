<?php

namespace App\Entity\Action;

use App\Entity\OnboardingTask;
use App\Service\EmailService;

class EmailAction implements TaskActionInterface
{
    private ?string $emailTemplate = null;
    private ?string $assignedEmail = null;

    private ?EmailService $emailService = null;

    public function setEmailService(EmailService $service): void
    {
        $this->emailService = $service;
    }

    public function getEmailTemplate(): ?string
    {
        return $this->emailTemplate;
    }

    public function setEmailTemplate(?string $template): self
    {
        $this->emailTemplate = $template;
        return $this;
    }

    public function getAssignedEmail(): ?string
    {
        return $this->assignedEmail;
    }

    public function setAssignedEmail(?string $email): self
    {
        $this->assignedEmail = $email;
        return $this;
    }

    public function execute(OnboardingTask $task): void
    {
        if (!$this->emailService) {
            throw new \RuntimeException('EmailService not provided');
        }

        $recipient = $this->assignedEmail ?: $task->getFinalAssignedEmail();
        if (null === $recipient) {
            return;
        }

        $content = $this->emailTemplate ? $this->emailService->renderTemplate($this->emailTemplate, $task) : '';
        $this->emailService->sendEmail($recipient, 'Aufgabe fällig: '.$task->getTitle(), $content);
    }
}
