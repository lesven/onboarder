<?php

namespace App\Entity\Action;

use App\Entity\OnboardingTask;
use App\Service\EmailService;

class ApiCallAction implements TaskActionInterface
{
    private ?string $apiUrl = null;

    private ?EmailService $emailService = null;

    public function setEmailService(EmailService $service): void
    {
        $this->emailService = $service;
    }

    public function getApiUrl(): ?string
    {
        return $this->apiUrl;
    }

    public function setApiUrl(?string $url): self
    {
        $this->apiUrl = $url;
        return $this;
    }

    public function execute(OnboardingTask $task): void
    {
        if (!$this->apiUrl || !$this->emailService) {
            throw new \RuntimeException('API URL or EmailService not set');
        }

        $command = $this->emailService->renderUrlEncodedTemplate($this->apiUrl, $task);
        $command = str_replace([' \\', '\\ ', "\n", "\r"], [' ', ' ', ' ', ''], $command);
        $command = preg_replace('/\s+/', ' ', trim($command));

        // Debug: Output the command that will be executed
        error_log("Executing API command: " . $command);

        exec($command, $output, $exitCode);
        
        // Debug: Output the result
        error_log("API command exit code: " . $exitCode);
        error_log("API command output: " . implode("\n", $output));
        
        if (0 !== $exitCode) {
            throw new \RuntimeException('API call failed with exit code '.$exitCode . '. Output: ' . implode("\n", $output));
        }
    }
}
