<?php

namespace App\Command;

use App\Entity\OnboardingTask;
use App\Service\EmailService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand('app:process-tasks', 'Verarbeitet fällige Aufgaben und führt die konfigurierte Aktion aus.')]
class ProcessTasksCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly EmailService $emailService
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $today = new \DateTimeImmutable('today');

        /** @var \App\Repository\OnboardingTaskRepository $repo */
        $repo = $this->entityManager->getRepository(OnboardingTask::class);
        $tasks = $repo->findTasksDueForDate($today);

        foreach ($tasks as $task) {
            try {
                switch ($task->getActionType()) {
                    case OnboardingTask::ACTION_EMAIL:
                        $recipient = $task->getFinalAssignedEmail();
                        if (null === $recipient) {
                            continue 2;
                        }
                        $content = $task->getEmailTemplate() ?? '';
                        $content = $this->emailService->renderTemplate($content, $task);
                        $this->emailService->sendEmail(
                            $recipient,
                            'Aufgabe fällig: ' . $task->getTitle(),
                            $content
                        );
                        $task->setEmailSentAt(new \DateTimeImmutable());
                        break;
                    case OnboardingTask::ACTION_API:
                        $commandTemplate = $task->getApiUrl();
                        if (!$commandTemplate) {
                            continue 2;
                        }
                        $command = $this->emailService->renderUrlEncodedTemplate($commandTemplate, $task);
                        exec($command, $_, $exitCode);
                        if (0 !== $exitCode) {
                            throw new \RuntimeException('API call failed with exit code ' . $exitCode);
                        }
                        $task->setEmailSentAt(new \DateTimeImmutable());
                        break;
                    default:
                        break;
                }
            } catch (\Throwable $e) {
                $output->writeln('<error>' . $e->getMessage() . '</error>');
            }
        }

        $this->entityManager->flush();
        $output->writeln(sprintf('<info>%d Tasks verarbeitet.</info>', count($tasks)));

        return Command::SUCCESS;
    }
}
