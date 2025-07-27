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

#[AsCommand(
    name: 'app:process-tasks',
    description: 'Process onboarding tasks that are due'
)]
class ProcessTasksCommand extends Command
{
    public function __construct(
        private OnboardingTaskRepository $onboardingTaskRepository,
        private EmailService $emailService,
        private EntityManagerInterface $entityManager
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
                $task->getTask()->getSubject(), 
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

        // Für jetzt nur Email Actions unterstützen
        if (str_starts_with($actionType, 'email_')) {
            return $this->processEmailAction($task, $io);
        } else {
            $io->warning(sprintf('Unsupported action type: %s', $actionType));
            return false;
        }
    }

    private function processEmailAction(OnboardingTask $task, SymfonyStyle $io): bool
    {
        try {
            $result = $this->emailService->sendTaskEmail($task);
            $io->writeln(sprintf('Email sent: %s', $result ? 'SUCCESS' : 'FAILED'));
            
            if ($result) {
                // Markiere als versendet (setze sentAt)
                $task->setSentAt(new \DateTimeImmutable());
                $this->entityManager->persist($task);
                $this->entityManager->flush();
                $io->writeln('Task marked as sent');
            }
            
            return $result;
        } catch (\Exception $e) {
            $io->error(sprintf('Error sending email: %s', $e->getMessage()));
            return false;
        }
    }
}
