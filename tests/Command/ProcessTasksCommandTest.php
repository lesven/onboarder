<?php

namespace App\Command;

// Override exec within this namespace for testing
function exec(string $command, ?array &$output = null, ?int &$exitCode = null): void
{
    \App\Tests\Command\ProcessTasksCommandTest::recordExec($command, $output, $exitCode);
}

namespace App\Entity\Action;

function exec(string $command, ?array &$output = null, ?int &$exitCode = null): void
{
    \App\Tests\Command\ProcessTasksCommandTest::recordExec($command, $output, $exitCode);
}

namespace App\Tests\Command;

use App\Command\ProcessTasksCommand;
use App\Entity\OnboardingTask;
use App\Repository\OnboardingTaskRepository;
use App\Service\EmailService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class ProcessTasksCommandTest extends TestCase
{
    private static array $execCommands = [];
    private static int $nextExitCode = 0;

    public static function recordExec(string $command, ?array &$output = null, ?int &$exitCode = null): void
    {
        self::$execCommands[] = $command;
        $output = ['ok'];
        $exitCode = self::$nextExitCode;
    }

    private EntityManagerInterface $em;
    private EmailService $emailService;
    private OnboardingTaskRepository $repo;
    private CommandTester $tester;
    private ProcessTasksCommand $command;

    protected function setUp(): void
    {
        self::$execCommands = [];
        self::$nextExitCode = 0;

        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->emailService = $this->createMock(EmailService::class);
        $this->repo = $this->createMock(OnboardingTaskRepository::class);

        $this->em->method('getRepository')->with(OnboardingTask::class)->willReturn($this->repo);

        $this->command = new ProcessTasksCommand($this->em, $this->emailService);
        $this->tester = new CommandTester($this->command);
    }

    public function testExecuteProcessesEmailAndApiTasks(): void
    {
        $taskEmail = (new OnboardingTask());
        $emailAction = (new \App\Entity\Action\EmailAction())
            ->setAssignedEmail('a@example.com')
            ->setEmailTemplate('tpl');
        $taskEmail->setTaskAction($emailAction);

        $taskApi = (new OnboardingTask());
        $apiAction = (new \App\Entity\Action\ApiCallAction())
            ->setApiUrl('curl http://example.com');
        $taskApi->setTaskAction($apiAction);

        $this->repo->method('findTasksDueForDate')->willReturn([$taskEmail, $taskApi]);

        $this->emailService->expects($this->once())
            ->method('renderTemplate')
            ->willReturn('content');
        $this->emailService->expects($this->once())
            ->method('sendEmail');
        $this->emailService->expects($this->once())
            ->method('renderUrlEncodedTemplate')
            ->with('curl http://example.com', $taskApi)
            ->willReturn('curl http://example.com');

        $this->em->expects($this->once())->method('flush');

        $exit = $this->tester->execute([]);

        $this->assertSame(Command::SUCCESS, $exit);
        $this->assertStringContainsString('2 Tasks verarbeitet.', $this->tester->getDisplay());
        $this->assertCount(1, self::$execCommands);
        $this->assertSame('curl http://example.com', self::$execCommands[0]);
    }

    public function testCommandMetadata(): void
    {
        $this->assertSame('app:process-tasks', $this->command->getName());
        $this->assertNotEmpty($this->command->getDescription());
    }
}
