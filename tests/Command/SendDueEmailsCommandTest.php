<?php

namespace App\Tests\Command;

use App\Command\SendDueEmailsCommand;
use App\Entity\OnboardingTask;
use App\Repository\OnboardingTaskRepository;
use App\Service\EmailService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class SendDueEmailsCommandTest extends TestCase
{
    private EntityManagerInterface $entityManager;
    private EmailService $emailService;
    private OnboardingTaskRepository $repository;
    private CommandTester $tester;
    private SendDueEmailsCommand $command;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->emailService = $this->createMock(EmailService::class);
        $this->repository = $this->createMock(OnboardingTaskRepository::class);

        $this->entityManager
            ->method('getRepository')
            ->with(OnboardingTask::class)
            ->willReturn($this->repository);

        $this->command = new SendDueEmailsCommand($this->entityManager, $this->emailService);
        $this->tester = new CommandTester($this->command);
    }

    public function testExecuteSendsEmailsForDueTasks(): void
    {
        $task1 = (new OnboardingTask())
            ->setActionType(OnboardingTask::ACTION_EMAIL)
            ->setAssignedEmail('a@example.com')
            ->setEmailTemplate('tpl');
        $task2 = (new OnboardingTask())
            ->setActionType(OnboardingTask::ACTION_EMAIL)
            ->setAssignedEmail('b@example.com')
            ->setEmailTemplate('tpl2');

        $this->repository
            ->method('findTasksDueForDate')
            ->willReturn([$task1, $task2]);

        $this->emailService
            ->expects($this->exactly(2))
            ->method('renderTemplate')
            ->willReturn('content');
        $this->emailService
            ->expects($this->exactly(2))
            ->method('sendEmail');

        $this->entityManager->expects($this->once())->method('flush');

        $exitCode = $this->tester->execute([]);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('2 E-Mails verarbeitet.', $this->tester->getDisplay());
    }

    public function testExecuteSkipsTasksWithoutRecipient(): void
    {
        $task = (new OnboardingTask())
            ->setActionType(OnboardingTask::ACTION_EMAIL)
            ->setEmailTemplate('tpl');

        $this->repository
            ->method('findTasksDueForDate')
            ->willReturn([$task]);

        $this->emailService->expects($this->never())->method('sendEmail');
        $this->entityManager->expects($this->once())->method('flush');

        $exitCode = $this->tester->execute([]);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('1 E-Mails verarbeitet.', $this->tester->getDisplay());
    }

    public function testCommandMetadata(): void
    {
        $this->assertSame('app:send-due-emails', $this->command->getName());
        $this->assertNotEmpty($this->command->getDescription());
    }
}
