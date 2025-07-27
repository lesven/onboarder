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
            $action = $task->getTaskAction();

            if (null === $action) {
                // Fallback to legacy fields
                switch ($task->getActionType()) {
                    case OnboardingTask::ACTION_EMAIL:
                        $action = (new \App\Entity\Action\EmailAction())
                            ->setEmailTemplate($task->getEmailTemplate())
                            ->setAssignedEmail($task->getAssignedEmail());
                        break;
                    case OnboardingTask::ACTION_API:
                        $action = (new \App\Entity\Action\ApiCallAction())
                            ->setApiUrl($task->getApiUrl());
                        break;
                    default:
                        $action = null;
                        break;
                }
            }

            if (!$action) {
                continue;
            }

            try {
                if ($action instanceof \App\Entity\Action\EmailAction || $action instanceof \App\Entity\Action\ApiCallAction) {
                    $action->setEmailService($this->emailService);
                }
                $action->execute($task);
                $task->setEmailSentAt(new \DateTimeImmutable());
            } catch (\Throwable $e) {
                $output->writeln('<error>'.$e->getMessage().'</error>');
            }
        }

        $this->entityManager->flush();
        $output->writeln(sprintf('<info>%d Tasks verarbeitet.</info>', count($tasks)));

        return Command::SUCCESS;
    }
}
