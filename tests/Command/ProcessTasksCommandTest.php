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
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

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
    private HttpClientInterface $httpClient;
    private CommandTester $tester;
    private ProcessTasksCommand $command;

    protected function setUp(): void
    {
        self::$execCommands = [];
        self::$nextExitCode = 0;

        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->emailService = $this->createMock(EmailService::class);
        $this->repo = $this->createMock(OnboardingTaskRepository::class);
        $this->httpClient = $this->createMock(HttpClientInterface::class);

        $this->em->method('getRepository')->with(OnboardingTask::class)->willReturn($this->repo);

        $this->command = new ProcessTasksCommand($this->repo, $this->emailService, $this->em, $this->httpClient);
        $this->tester = new CommandTester($this->command);
    }

    public function testExecuteProcessesEmailAndApiTasks(): void
    {
        $date = new \DateTimeImmutable('2025-07-27');
        
        // Create email task
        $taskEmail = new OnboardingTask();
        $taskEmail->setTitle('Email Task');
        $taskEmail->setActionType('email');
        $taskEmail->setEmailTemplate('Hello {{firstName}}');
        $taskEmail->setAssignedEmail('test@example.com');

        // Create API task
        $taskApi = new OnboardingTask();
        $taskApi->setTitle('API Task');
        $taskApi->setActionType('api');
        $taskApi->setApiUrl('http://example.com/api');

        $this->repo->method('findTasksDueForDate')
            ->with($this->callback(function($arg) use ($date) {
                return $arg->format('Y-m-d') === $date->format('Y-m-d');
            }))
            ->willReturn([$taskEmail, $taskApi]);

        // Mock email service
        $this->emailService->expects($this->once())
            ->method('renderTemplate')
            ->with('Hello {{firstName}}', $taskEmail)
            ->willReturn('Hello John');
            
        $this->emailService->expects($this->once())
            ->method('sendEmail')
            ->with('test@example.com', 'Email Task', 'Hello John');

        // Mock HTTP client for API call
        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockResponse->method('getStatusCode')->willReturn(200);
        $mockResponse->method('getContent')->willReturn('{"success": true}');
        
        $this->httpClient->expects($this->once())
            ->method('request')
            ->with('GET', 'http://example.com/api')
            ->willReturn($mockResponse);

        $this->em->expects($this->once())->method('persist');
        $this->em->expects($this->once())->method('flush');

        $exit = $this->tester->execute(['date' => '2025-07-27']);

        $this->assertSame(Command::SUCCESS, $exit);
        $display = $this->tester->getDisplay();
        $this->assertStringContainsString('Found 2 tasks to process', $display);
        $this->assertStringContainsString('Processed: 2, Errors: 0', $display);
    }

    public function testCommandMetadata(): void
    {
        $this->assertSame('app:process-tasks', $this->command->getName());
        $this->assertNotEmpty($this->command->getDescription());
    }
}
