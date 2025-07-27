<?php

namespace App\Tests\Command;

use App\Command\UserCommand;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserCommandTest extends TestCase
{
    private EntityManagerInterface $em;
    private UserPasswordHasherInterface $hasher;
    private UserRepository $repo;
    private CommandTester $tester;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->hasher = $this->createMock(UserPasswordHasherInterface::class);
        $this->repo = $this->createMock(UserRepository::class);

        $this->em->method('getRepository')->with(User::class)->willReturn($this->repo);

        $command = new UserCommand($this->em, $this->hasher);
        $this->tester = new CommandTester($command);
    }

    public function testExecuteWithInvalidEmail(): void
    {
        $exit = $this->tester->execute(['email' => 'invalid', 'password' => '12345678']);
        $this->assertSame(Command::FAILURE, $exit);
        $this->assertStringContainsString('Ungültige E-Mail-Adresse', $this->tester->getDisplay());
    }

    public function testExecuteWithShortPassword(): void
    {
        $exit = $this->tester->execute(['email' => 'test@example.com', 'password' => 'short']);
        $this->assertSame(Command::FAILURE, $exit);
        $this->assertStringContainsString('Passwort muss mindestens 8 Zeichen lang sein', $this->tester->getDisplay());
    }

    public function testExecuteCreatesNewUser(): void
    {
        $this->repo->method('findOneBy')->willReturn(null);
        $this->hasher->expects($this->once())->method('hashPassword');
        $this->em->expects($this->once())->method('persist');
        $this->em->expects($this->once())->method('flush');

        $exit = $this->tester->execute(['email' => 'new@example.com', 'password' => 'securepass']);

        $this->assertSame(Command::SUCCESS, $exit);
        $this->assertStringContainsString('Benutzer wurde erstellt', $this->tester->getDisplay());
    }

    public function testExecuteUpdatesExistingUser(): void
    {
        $user = new User();
        $this->repo->method('findOneBy')->willReturn($user);
        $this->hasher->expects($this->once())->method('hashPassword')->with($user, 'newpass123');
        $this->em->expects($this->once())->method('persist')->with($user);
        $this->em->expects($this->once())->method('flush');

        $exit = $this->tester->execute(['email' => 'exists@example.com', 'password' => 'newpass123']);

        $this->assertSame(Command::SUCCESS, $exit);
        $this->assertStringContainsString('Passwort wurde aktualisiert', $this->tester->getDisplay());
    }
}
