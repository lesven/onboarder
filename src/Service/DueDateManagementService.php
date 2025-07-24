<?php

namespace App\Service;

use App\Entity\Onboarding;
use App\Entity\OnboardingTask;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Verantwortlich für das Management von Fälligkeitsdaten.
 */
class DueDateManagementService
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    /**
     * Aktualisiert alle relativen Fälligkeiten nach Anpassung des Eintrittsdatums.
     */
    public function updateDueDates(Onboarding $onboarding): void
    {
        $entryDate = $onboarding->getEntryDate();
        if (!$entryDate) {
            return;
        }

        foreach ($onboarding->getOnboardingTasks() as $task) {
            $this->updateTaskDueDate($task, $entryDate);
        }

        $this->entityManager->flush();
    }

    /**
     * Aktualisiert das Fälligkeitsdatum einer einzelnen Task.
     */
    private function updateTaskDueDate(OnboardingTask $task, \DateTimeImmutable $entryDate): void
    {
        if (null !== $task->getDueDaysFromEntry()) {
            $task->setDueDate((clone $entryDate)->modify(sprintf('%+d days', $task->getDueDaysFromEntry())));
            $task->setUpdatedAt(new \DateTimeImmutable());
        }
    }
}
