<?php

namespace App\Service;

use App\Entity\OnboardingTask;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Verantwortlich für Status-Management von Tasks.
 */
class TaskStatusService
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    /**
     * Schaltet den Status einer Aufgabe um.
     *
     * @return array{string,string} Typ und Meldung für Flash-Nachricht
     */
    public function toggleStatus(OnboardingTask $task): array
    {
        if (OnboardingTask::STATUS_COMPLETED === $task->getStatus()) {
            $task->setStatus(OnboardingTask::STATUS_PENDING);
            $task->setCompletedAt(null);
            $type = 'info';
            $message = 'Aufgabe "'.$task->getTitle().'" wurde als ausstehend markiert.';
        } else {
            $task->markAsCompleted();
            $type = 'success';
            $message = 'Aufgabe "'.$task->getTitle().'" wurde als erledigt markiert.';
        }

        $this->entityManager->flush();

        return [$type, $message];
    }

    /**
     * Markiert eine Task als erledigt.
     */
    public function markAsCompleted(OnboardingTask $task): void
    {
        $task->markAsCompleted();
        $this->entityManager->flush();
    }

    /**
     * Markiert eine Task als ausstehend.
     */
    public function markAsPending(OnboardingTask $task): void
    {
        $task->setStatus(OnboardingTask::STATUS_PENDING);
        $task->setCompletedAt(null);
        $this->entityManager->flush();
    }
}
