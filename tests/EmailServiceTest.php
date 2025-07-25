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
        $service = new EmailService(
            $this->createMock(EntityManagerInterface::class),
            new PasswordEncryptionService($parameterBag)
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
}
