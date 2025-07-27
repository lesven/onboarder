<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand('app:user', 'Legt einen Benutzer an oder aktualisiert das Passwort.')]
class UserCommand extends Command
{
    public function __construct(private readonly EntityManagerInterface $em, private readonly UserPasswordHasherInterface $hasher)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('email', InputArgument::REQUIRED, 'E-Mail-Adresse des Benutzers');
        $this->addArgument('password', InputArgument::REQUIRED, 'Neues Passwort (min 8 Zeichen)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $email = (string) $input->getArgument('email');
        $password = (string) $input->getArgument('password');

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $output->writeln('<error>Ungültige E-Mail-Adresse.</error>');

            return Command::FAILURE;
        }

        if (strlen($password) < 8) {
            $output->writeln('<error>Passwort muss mindestens 8 Zeichen lang sein.</error>');

            return Command::FAILURE;
        }

        $repo = $this->em->getRepository(User::class);
        $user = $repo->findOneBy(['username' => $email]);
        $created = false;
        if (!$user) {
            $user = new User();
            $user->setUsername($email);
            $user->setEmail($email);
            $user->setRoles(['ROLE_USER']);
            $created = true;
        }

        $user->setPassword($this->hasher->hashPassword($user, $password));
        $this->em->persist($user);
        $this->em->flush();

        if ($created) {
            $output->writeln('<info>Benutzer wurde erstellt.</info>');
        } else {
            $output->writeln('<info>Passwort wurde aktualisiert.</info>');
        }

        return Command::SUCCESS;
    }
}
