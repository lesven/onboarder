<?php

namespace App\Tests\Command;

use App\Command\TestEmailCommand;
use App\Service\EmailService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class TestEmailCommandTest extends TestCase
{
    public function testExecute(): void
    {
        $service = $this->createMock(EmailService::class);
        $service->expects($this->once())
            ->method('sendTestMail')
            ->with('test@example.com', 'Testnachricht', 'Dies ist eine Test-E-Mail.');

        $command = new TestEmailCommand($service);
        $tester = new CommandTester($command);

        $exitCode = $tester->execute(['recipient' => 'test@example.com']);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('E-Mail wurde versendet', $tester->getDisplay());
    }

    public function testCommandMetadata(): void
    {
        $service = $this->createMock(EmailService::class);
        $command = new TestEmailCommand($service);

        $this->assertSame('app:test-email', $command->getName());
        $this->assertNotEmpty($command->getDescription());
    }
}
