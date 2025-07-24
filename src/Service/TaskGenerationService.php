<?php

namespace App\Service;

use App\Entity\Onboarding;
use App\Entity\OnboardingTask;
use App\Entity\TaskBlock;
use App\Entity\Task;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Verantwortlich für die Generierung von OnboardingTasks aus Templates.
 */
class TaskGenerationService
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    /**
     * Erzeugt OnboardingTasks basierend auf dem zugewiesenen OnboardingType.
     */
    public function generateForOnboarding(Onboarding $onboarding): void
    {
        $taskBlocks = $this->collectTaskBlocks($onboarding);
        
        foreach ($taskBlocks as $block) {
            foreach ($block->getTasks() as $templateTask) {
                $task = $this->createTaskFromTemplate($templateTask, $onboarding, $block);
                $this->entityManager->persist($task);
            }
        }

        $this->entityManager->flush();
    }

    /**
     * Sammelt alle eindeutigen TaskBlocks für das Onboarding.
     *
     * @return TaskBlock[]
     */
    private function collectTaskBlocks(Onboarding $onboarding): array
    {
        $onboardingType = $onboarding->getOnboardingType();
        if (!$onboardingType) {
            return [];
        }

        $taskBlocks = [];
        $processedIds = [];

        // TaskBlocks vom OnboardingType sammeln
        foreach ($onboardingType->getTaskBlocks() as $block) {
            if (!in_array($block->getId(), $processedIds, true)) {
                $taskBlocks[] = $block;
                $processedIds[] = $block->getId();
            }
        }

        // TaskBlocks vom BaseType sammeln (falls vorhanden)
        if ($onboardingType->getBaseType()) {
            foreach ($onboardingType->getBaseType()->getTaskBlocks() as $block) {
                if (!in_array($block->getId(), $processedIds, true)) {
                    $taskBlocks[] = $block;
                    $processedIds[] = $block->getId();
                }
            }
        }

        return $taskBlocks;
    }

    /**
     * Erstellt eine OnboardingTask aus einer Template-Task.
     */
    private function createTaskFromTemplate(Task $templateTask, Onboarding $onboarding, TaskBlock $block): OnboardingTask
    {
        $task = new OnboardingTask();
        $task->setTitle($templateTask->getTitle());
        $task->setDescription($templateTask->getDescription());
        $task->setSortOrder($templateTask->getSortOrder());
        $task->setTaskBlock($block);
        $task->setTemplateTask($templateTask);
        $task->setOnboarding($onboarding);

        $this->applyTemplateDueDate($task, $templateTask, $onboarding->getEntryDate());
        $this->applyTemplateAssignments($task, $templateTask);
        $this->applyTemplateEmail($task, $templateTask);

        return $task;
    }

    /**
     * Wendet Fälligkeitsdatum aus Template an.
     */
    private function applyTemplateDueDate(OnboardingTask $task, Task $templateTask, ?\DateTimeImmutable $entryDate): void
    {
        if ($templateTask->getDueDate()) {
            $task->setDueDate($templateTask->getDueDate());
            return;
        }

        if (null !== $templateTask->getDueDaysFromEntry()) {
            $days = $templateTask->getDueDaysFromEntry();
            if ($entryDate) {
                $task->setDueDate($this->calculateDueDate($entryDate, $days));
            }
            $task->setDueDaysFromEntry($days);
        }
    }

    /**
     * Berechnet das Fälligkeitsdatum basierend auf dem Eintrittsdatum und der Anzahl der Tage.
     */
    private function calculateDueDate(\DateTimeImmutable $entryDate, int $days): \DateTimeImmutable
    {
        return (clone $entryDate)->modify(sprintf('%+d days', $days));
    }

    /**
     * Wendet Zuweisungen aus Template an.
     */
    private function applyTemplateAssignments(OnboardingTask $task, Task $templateTask): void
    {
        if ($templateTask->getAssignedRole()) {
            $task->setAssignedRole($templateTask->getAssignedRole());
        }
        if ($templateTask->getAssignedEmail()) {
            $task->setAssignedEmail($templateTask->getAssignedEmail());
        }
    }

    /**
     * Wendet E-Mail-Konfiguration aus Template an.
     */
    private function applyTemplateEmail(OnboardingTask $task, Task $templateTask): void
    {
        if ($templateTask->getEmailTemplate()) {
            $task->setEmailTemplate($templateTask->getEmailTemplate());
            $task->setSendEmail(true);
        }
    }
}
