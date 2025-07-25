<?php

namespace App\Command;

use App\Service\EmailService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand('app:test-email', 'Sendet eine Test-E-Mail basierend auf den gespeicherten Einstellungen.')]
class TestEmailCommand extends Command
{
    public function __construct(private readonly EmailService $emailService)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('recipient', InputArgument::REQUIRED, 'Empfänger Adresse');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $recipient = (string) $input->getArgument('recipient');
        $this->emailService->sendTestMail($recipient, 'Testnachricht', 'Dies ist eine Test-E-Mail.');
        $output->writeln('<info>E-Mail wurde versendet (oder Versuch gestartet).</info>');

        return Command::SUCCESS;
    }
}
