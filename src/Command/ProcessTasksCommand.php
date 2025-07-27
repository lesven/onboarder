<?php

namespace App\Command;

use App\Entity\OnboardingTask;
use App\Repository\OnboardingTaskRepository;
use App\Service\EmailService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[AsCommand(
    name: 'app:process-tasks',
    description: 'Process onboarding tasks that are due'
)]
class ProcessTasksCommand extends Command
{
    public function __construct(
        private OnboardingTaskRepository $onboardingTaskRepository,
        private EmailService $emailService,
        private EntityManagerInterface $entityManager,
        private HttpClientInterface $httpClient
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('date', InputArgument::OPTIONAL, 'Date to process (Y-m-d format)', date('Y-m-d'));
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $date = $input->getArgument('date');

        $io->info(sprintf('Processing tasks for date: %s', $date));

        try {
            $dateObj = \DateTimeImmutable::createFromFormat('Y-m-d', $date);
            if (!$dateObj) {
                throw new \Exception('Invalid date format');
            }
        } catch (\Exception $e) {
            $io->error(sprintf('Invalid date format: %s', $date));
            return Command::FAILURE;
        }

        // Hole alle Tasks für das Datum
        $tasks = $this->onboardingTaskRepository->findTasksDueForDate($dateObj);
        $io->info(sprintf('Found %d tasks to process', count($tasks)));

        $processedCount = 0;
        $errorCount = 0;

        foreach ($tasks as $task) {
            $io->writeln(sprintf('Processing task: %d - %s (Action: %s)', 
                $task->getId(), 
                $task->getTitle(), 
                $task->getActionType()
            ));

            try {
                if ($this->processTask($task, $io)) {
                    $processedCount++;
                    $io->success(sprintf('✓ Task %d processed successfully', $task->getId()));
                } else {
                    $errorCount++;
                    $io->error(sprintf('✗ Task %d failed to process', $task->getId()));
                }
            } catch (\Exception $e) {
                $errorCount++;
                $io->error(sprintf('✗ Task %d failed with exception: %s', $task->getId(), $e->getMessage()));
            }
        }

        $io->info(sprintf('Processing complete. Processed: %d, Errors: %d', $processedCount, $errorCount));

        return Command::SUCCESS;
    }

    private function processTask(OnboardingTask $task, SymfonyStyle $io): bool
    {
        $actionType = $task->getActionType();
        $io->writeln(sprintf('Processing action type: %s', $actionType));

        if (!$actionType) {
            $io->warning('No action type defined for task');
            return false;
        }

        // Handle verschiedene Action Types
        switch ($actionType) {
            case 'email':
            case 'email_immediate':
            case 'email_on_date':
            case 'email_relative':
                return $this->processEmailAction($task, $io);
            
            case 'api':
            case 'api_call':
                return $this->processApiAction($task, $io);
            
            default:
                $io->warning(sprintf('Unsupported action type: %s', $actionType));
                return false;
        }
    }

    private function processEmailAction(OnboardingTask $task, SymfonyStyle $io): bool
    {
        try {
            $template = $task->getEmailTemplate();
            if (!$template) {
                $io->error('No email template configured for task');
                return false;
            }

            $assignedEmail = $task->getFinalAssignedEmail();
            if (!$assignedEmail) {
                $io->error('No assigned email for task');
                return false;
            }

            // Render template
            $htmlContent = $this->emailService->renderTemplate($template, $task);
            $subject = $task->getTitle() ?? 'Onboarding Task';
            
            $io->writeln(sprintf('Sending email to: %s', $assignedEmail));
            $this->emailService->sendEmail($assignedEmail, $subject, $htmlContent);
            
            $io->writeln('Email sent successfully');
            
            // Markiere als versendet
            $task->setEmailSentAt(new \DateTimeImmutable());
            $this->entityManager->persist($task);
            $this->entityManager->flush();
            $io->writeln('Task marked as sent');
            
            return true;
        } catch (\Exception $e) {
            $io->error(sprintf('Error sending email: %s', $e->getMessage()));
            return false;
        }
    }

    private function processApiAction(OnboardingTask $task, SymfonyStyle $io): bool
    {
        try {
            $apiUrl = $task->getApiUrl();
            if (!$apiUrl) {
                $io->error('No API URL configured for task');
                return false;
            }

            $io->writeln(sprintf('Making API call to: %s', $apiUrl));
            
            // Parse curl command to extract URL and data
            if (str_contains($apiUrl, 'curl')) {
                return $this->executeCurlCommand($apiUrl, $task, $io);
            } else {
                // Simple HTTP request
                return $this->executeSimpleHttpRequest($apiUrl, $task, $io);
            }
            
        } catch (\Exception $e) {
            $io->error(sprintf('Error making API call: %s', $e->getMessage()));
            return false;
        }
    }

    private function executeCurlCommand(string $curlCommand, OnboardingTask $task, SymfonyStyle $io): bool
    {
        // Extract URL from curl command
        preg_match('/curl\s+(?:-X\s+\w+\s+)?([^\s]+)/', $curlCommand, $urlMatches);
        if (!isset($urlMatches[1])) {
            $io->error('Could not extract URL from curl command');
            return false;
        }
        
        $url = $urlMatches[1];
        $io->writeln(sprintf('Extracted URL: %s', $url));
        
        // Extract JSON data
        preg_match("/-d\s+'([^']+)'/", $curlCommand, $dataMatches);
        if (!isset($dataMatches[1])) {
            $io->error('Could not extract JSON data from curl command');
            return false;
        }
        
        $jsonData = $dataMatches[1];
        $io->writeln('Extracted JSON data');
        
        // Replace placeholders with actual values
        $processedData = $this->emailService->renderTemplate($jsonData, $task);
        $io->writeln(sprintf('Processed data: %s', $processedData));
        
        // Validate JSON
        $decodedData = json_decode($processedData, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $io->error(sprintf('Invalid JSON data: %s', json_last_error_msg()));
            return false;
        }
        
        // Make HTTP request
        $response = $this->httpClient->request('POST', $url, [
            'headers' => [
                'Content-Type' => 'application/json',
            ],
            'json' => $decodedData,
            'timeout' => 30,
        ]);
        
        $statusCode = $response->getStatusCode();
        $io->writeln(sprintf('API Response status: %d', $statusCode));
        
        if ($statusCode >= 200 && $statusCode < 300) {
            $responseContent = $response->getContent();
            $io->writeln(sprintf('API Response: %s', substr($responseContent, 0, 200)));
            $io->success('API call completed successfully');
            
            // Markiere Task als abgeschlossen
            $task->setCompletedAt(new \DateTimeImmutable());
            $this->entityManager->persist($task);
            $this->entityManager->flush();
            $io->writeln('Task marked as completed');
            
            return true;
        } else {
            $io->error(sprintf('API call failed with status %d', $statusCode));
            return false;
        }
    }

    private function executeSimpleHttpRequest(string $url, OnboardingTask $task, SymfonyStyle $io): bool
    {
        // For simple URLs, make a GET request
        $response = $this->httpClient->request('GET', $url, [
            'timeout' => 30,
        ]);
        
        $statusCode = $response->getStatusCode();
        $io->writeln(sprintf('API Response status: %d', $statusCode));
        
        if ($statusCode >= 200 && $statusCode < 300) {
            $io->success('API call completed successfully');
            
            // Markiere Task als abgeschlossen
            $task->setCompletedAt(new \DateTimeImmutable());
            $this->entityManager->persist($task);
            $this->entityManager->flush();
            $io->writeln('Task marked as completed');
            
            return true;
        } else {
            $io->error(sprintf('API call failed with status %d', $statusCode));
            return false;
        }
    }
}
