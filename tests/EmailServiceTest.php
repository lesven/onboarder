<?php

namespace App\Tests;

use App\Entity\Onboarding;
use App\Entity\OnboardingTask;
use App\Service\EmailService;
use App\Service\PasswordEncryptionService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

class EmailServiceTest extends TestCase
{
    public function testRenderTemplateReplacesVariables(): void
    {
        $parameterBag = new ParameterBag(['kernel.secret' => 'test-secret-key']);
        $urlGenerator = $this->createMock(\Symfony\Component\Routing\Generator\UrlGeneratorInterface::class);
        $urlGenerator->method('generate')->willReturn('http://example.com');

        $service = new EmailService(
            $this->createMock(EntityManagerInterface::class),
            new PasswordEncryptionService($parameterBag),
            $urlGenerator
        );

        $onboarding = new Onboarding();
        $onboarding->setFirstName('Max');
        $onboarding->setLastName('Mustermann');
        $onboarding->setEntryDate(new \DateTimeImmutable('2024-01-01'));
        $onboarding->setManager('Chef');
        $onboarding->setBuddy('Buddy');

        // set ID via reflection
        $r = new \ReflectionProperty(Onboarding::class, 'id');
        $r->setAccessible(true);
        $r->setValue($onboarding, 7);

        $task = new OnboardingTask();
        $task->setOnboarding($onboarding);
        $r2 = new \ReflectionProperty(OnboardingTask::class, 'id');
        $r2->setAccessible(true);
        $r2->setValue($task, 42);

        $template = 'Hallo {{firstName}} {{lastName}}, {{entryDate}}, {{onboardingId}}, {{taskId}}, {{manager}}, {{buddy}}';
        $result = $service->renderTemplate($template, $task);

        $this->assertSame(
            'Hallo Max Mustermann, 2024-01-01, 7, 42, Chef, Buddy',
            $result
        );
    }

    public function testRenderUrlEncodedTemplateDoesNotEncodeEmails(): void
    {
        $parameterBag = new ParameterBag(['kernel.secret' => 'test-secret-key']);
        $urlGenerator = $this->createMock(\Symfony\Component\Routing\Generator\UrlGeneratorInterface::class);
        $urlGenerator->method('generate')->willReturn('http://example.com');

        $service = new EmailService(
            $this->createMock(EntityManagerInterface::class),
            new PasswordEncryptionService($parameterBag),
            $urlGenerator
        );

        $onboarding = new Onboarding();
        $onboarding->setFirstName('Max Müller'); // Name mit Umlaut für URL-Codierung
        $onboarding->setManager('Chef Manager'); // Manager setzen
        $onboarding->setBuddy('Buddy Name'); // Buddy setzen 
        $onboarding->setManagerEmail('manager@example.com');
        $onboarding->setBuddyEmail('buddy@test.org');

        $task = new OnboardingTask();
        $task->setOnboarding($onboarding);

        // Template mit E-Mail-Adressen und Namen
        $template = 'curl -X POST "https://api.example.com/user" -d "name={{firstName}}&manager={{managerEmail}}&buddy={{buddyEmail}}"';
        $result = $service->renderUrlEncodedTemplate($template, $task);

        // Namen sollten URL-codiert sein
        $this->assertStringContainsString('Max%20M%C3%BCller', $result);
        
        // E-Mail-Adressen sollten NICHT URL-codiert sein
        $this->assertStringContainsString('manager@example.com', $result);
        $this->assertStringContainsString('buddy@test.org', $result);
        
        // Stelle sicher, dass keine URL-codierten E-Mails vorhanden sind
        $this->assertStringNotContainsString('manager%40example.com', $result);
        $this->assertStringNotContainsString('buddy%40test.org', $result);
    }

    public function testRenderUrlEncodedTemplateWithEncodedEmailVariants(): void
    {
        $parameterBag = new ParameterBag(['kernel.secret' => 'test-secret-key']);
        $urlGenerator = $this->createMock(\Symfony\Component\Routing\Generator\UrlGeneratorInterface::class);
        $urlGenerator->method('generate')->willReturn('http://example.com');

        $service = new EmailService(
            $this->createMock(EntityManagerInterface::class),
            new PasswordEncryptionService($parameterBag),
            $urlGenerator
        );

        $onboarding = new Onboarding();
        $onboarding->setManager('Chef Manager'); // Manager setzen
        $onboarding->setBuddy('Buddy Name'); // Buddy setzen
        $onboarding->setManagerEmail('manager@example.com');

        $task = new OnboardingTask();
        $task->setOnboarding($onboarding);

        // Template mit normaler und URL-codierter E-Mail-Variable
        $template = 'Normal: {{managerEmail}}, URL-codiert: {{managerEmailEncoded}}';
        $result = $service->renderUrlEncodedTemplate($template, $task);

        // Normale Version sollte nicht URL-codiert sein
        $this->assertStringContainsString('Normal: manager@example.com', $result);
        
        // Encoded-Version sollte URL-codiert sein
        $this->assertStringContainsString('URL-codiert: manager%40example.com', $result);
    }
}
