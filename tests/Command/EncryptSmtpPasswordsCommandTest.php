<?php

namespace App\Tests\Command;

use App\Command\EncryptSmtpPasswordsCommand;
use App\Service\PasswordEncryptionService;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Result;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

class EncryptSmtpPasswordsCommandTest extends TestCase
{
    private EntityManagerInterface|MockObject $entityManager;
    private PasswordEncryptionService $encryptionService;
    private Connection|MockObject $connection;
    private EncryptSmtpPasswordsCommand $command;
    private CommandTester $commandTester;

    protected function setUp(): void
    {
        // Setup echte PasswordEncryptionService für Integration Tests
        $parameterBag = new ParameterBag(['kernel.secret' => 'test-secret-key-for-command-test']);
        $this->encryptionService = new PasswordEncryptionService($parameterBag);

        // Mock EntityManager und Connection
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->connection = $this->createMock(Connection::class);

        $this->entityManager
            ->method('getConnection')
            ->willReturn($this->connection);

        // Command erstellen
        $this->command = new EncryptSmtpPasswordsCommand(
            $this->entityManager,
            $this->encryptionService
        );

        $this->commandTester = new CommandTester($this->command);
    }

    public function testExecuteWithNoPasswords(): void
    {
        // Mock leeres Ergebnis
        $result = $this->createMock(Result::class);
        $result->method('fetchAllAssociative')->willReturn([]);

        $this->connection
            ->expects($this->once())
            ->method('executeQuery')
            ->with('SELECT id, smtp_password FROM email_settings WHERE smtp_password IS NOT NULL AND smtp_password != ""')
            ->willReturn($result);

        // Command ausführen
        $exitCode = $this->commandTester->execute([]);

        // Assertions
        $this->assertEquals(Command::SUCCESS, $exitCode);
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Keine SMTP-Passwörter zum Verschlüsseln gefunden', $output);
    }

    public function testExecuteWithPlaintextPasswords(): void
    {
        // Mock Datenbank-Ergebnis mit Klartext-Passwörtern
        $testData = [
            ['id' => 1, 'smtp_password' => 'password123'],
            ['id' => 2, 'smtp_password' => 'secret456'],
        ];

        $result = $this->createMock(Result::class);
        $result->method('fetchAllAssociative')->willReturn($testData);

        $this->connection
            ->expects($this->once())
            ->method('executeQuery')
            ->willReturn($result);

        // Erwarte 2 UPDATE-Statements für die 2 Passwörter
        $this->connection
            ->expects($this->exactly(2))
            ->method('executeStatement')
            ->willReturnCallback(function ($sql, $params) {
                $this->assertEquals('UPDATE email_settings SET smtp_password = ? WHERE id = ?', $sql);
                $this->assertIsArray($params);
                $this->assertCount(2, $params);
                $this->assertTrue($this->encryptionService->isEncrypted($params[0]));
                $this->assertContains($params[1], [1, 2]);
                return 1;
            });

        // Command ausführen
        $exitCode = $this->commandTester->execute([]);

        // Assertions
        $this->assertEquals(Command::SUCCESS, $exitCode);
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('2 Passwörter verschlüsselt', $output);
        $this->assertStringContainsString('0 bereits', $output); // Verkürzt wegen Zeilenwrap
    }

    public function testExecuteWithAlreadyEncryptedPasswords(): void
    {
        // Mock Datenbank-Ergebnis mit bereits verschlüsselten Passwörtern
        $encryptedPassword1 = $this->encryptionService->encrypt('password123');
        $encryptedPassword2 = $this->encryptionService->encrypt('secret456');

        $testData = [
            ['id' => 1, 'smtp_password' => $encryptedPassword1],
            ['id' => 2, 'smtp_password' => $encryptedPassword2],
        ];

        $result = $this->createMock(Result::class);
        $result->method('fetchAllAssociative')->willReturn($testData);

        $this->connection
            ->expects($this->once())
            ->method('executeQuery')
            ->willReturn($result);

        // Erwarte keine UPDATE-Statements, da alle bereits verschlüsselt sind
        $this->connection
            ->expects($this->never())
            ->method('executeStatement');

        // Command ausführen
        $exitCode = $this->commandTester->execute([]);

        // Assertions
        $this->assertEquals(Command::SUCCESS, $exitCode);
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('0 Passwörter verschlüsselt', $output);
        $this->assertStringContainsString('2 bereits', $output); // Verkürzt wegen Zeilenwrap
    }

    public function testExecuteWithMixedPasswords(): void
    {
        // Mock Datenbank-Ergebnis mit gemischten Passwörtern
        $encryptedPassword = $this->encryptionService->encrypt('alreadyEncrypted');

        $testData = [
            ['id' => 1, 'smtp_password' => 'plaintext123'],
            ['id' => 2, 'smtp_password' => $encryptedPassword],
            ['id' => 3, 'smtp_password' => 'anotherPlaintext456'],
        ];

        $result = $this->createMock(Result::class);
        $result->method('fetchAllAssociative')->willReturn($testData);

        $this->connection
            ->expects($this->once())
            ->method('executeQuery')
            ->willReturn($result);

        // Erwarte 2 UPDATE-Statements nur für die Klartext-Passwörter
        $updateCallCount = 0;
        $this->connection
            ->expects($this->exactly(2))
            ->method('executeStatement')
            ->willReturnCallback(function ($sql, $params) use (&$updateCallCount) {
                $updateCallCount++;
                $this->assertEquals('UPDATE email_settings SET smtp_password = ? WHERE id = ?', $sql);
                $this->assertIsArray($params);
                $this->assertCount(2, $params);
                $this->assertTrue($this->encryptionService->isEncrypted($params[0]));
                // Nur IDs 1 und 3 sollten aktualisiert werden (Klartext-Passwörter)
                $this->assertContains($params[1], [1, 3]);
                return 1;
            });

        // Command ausführen
        $exitCode = $this->commandTester->execute([]);

        // Assertions
        $this->assertEquals(Command::SUCCESS, $exitCode);
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('2 Passwörter verschlüsselt', $output);
        $this->assertStringContainsString('1 bereits', $output); // Verkürzt wegen Zeilenwrap
    }

    public function testCommandName(): void
    {
        $this->assertEquals('app:encrypt-smtp-passwords', $this->command->getName());
    }

    public function testCommandDescription(): void
    {
        $this->assertEquals(
            'Verschlüsselt alle vorhandenen SMTP-Passwörter in der Datenbank',
            $this->command->getDescription()
        );
    }

    public function testExecuteDisplaysTitle(): void
    {
        // Mock leeres Ergebnis für einfachen Test
        $result = $this->createMock(Result::class);
        $result->method('fetchAllAssociative')->willReturn([]);

        $this->connection
            ->method('executeQuery')
            ->willReturn($result);

        // Command ausführen
        $this->commandTester->execute([]);

        // Prüfe dass der Titel angezeigt wird
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('SMTP-Passwort Verschlüsselung', $output);
    }
}
