<?php

namespace App\Command;

use App\Entity\OnboardingTask;
use App\Service\EmailService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand('app:send-due-emails', 'Versendet alle heute fälligen Aufgaben-E-Mails.')]
class SendDueEmailsCommand extends Command
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

            if (!$action instanceof \App\Entity\Action\EmailAction) {
                continue;
            }

            $action->setEmailService($this->emailService);

            try {
                $action->execute($task);
                $task->setEmailSentAt(new \DateTimeImmutable());
            } catch (\Throwable $e) {
                $output->writeln('<error>'.$e->getMessage().'</error>');
            }
        }

        $this->entityManager->flush();
        $output->writeln(sprintf('<info>%d E-Mails verarbeitet.</info>', count($tasks)));

        return Command::SUCCESS;
    }
}
