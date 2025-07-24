<?php

namespace App\Service;

use App\Entity\Onboarding;
use App\Entity\OnboardingTask;
use App\Entity\Role;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Verantwortlich für das Mapping zwischen HTTP-Requests und OnboardingTask-Entitäten.
 */
class TaskRequestMappingService
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    /**
     * Erstellt eine neue OnboardingTask anhand der Request-Daten.
     */
    public function createOnboardingTask(Onboarding $onboarding, Request $request): OnboardingTask
    {
        $task = new OnboardingTask();
        $task->setOnboarding($onboarding);
        $this->populateFromRequest($task, $onboarding, $request);

        $this->entityManager->persist($task);
        $this->entityManager->flush();

        return $task;
    }

    /**
     * Aktualisiert eine bestehende OnboardingTask.
     */
    public function updateOnboardingTask(OnboardingTask $task, Request $request): OnboardingTask
    {
        $this->populateFromRequest($task, $task->getOnboarding(), $request);
        $task->setUpdatedAt(new \DateTimeImmutable());
        $this->entityManager->flush();

        return $task;
    }

    /**
     * Befüllt eine Task mit Request-Daten.
     */
    private function populateFromRequest(OnboardingTask $task, Onboarding $onboarding, Request $request): void
    {
        $task->setTitle($request->request->get('title'));
        $task->setDescription($request->request->get('description'));
        $task->setSortOrder((int) ($request->request->get('sortOrder') ?: 0));

        $this->applyDueDateFromRequest($task, $onboarding, $request);
        $this->applyAssignmentFromRequest($task, $request);
        $this->applyEmailFromRequest($task, $request);
    }

    /**
     * Wendet Fälligkeitsdatum aus Request an.
     */
    private function applyDueDateFromRequest(OnboardingTask $task, Onboarding $onboarding, Request $request): void
    {
        $dueType = $request->request->get('dueDateType', 'none');

        if ('fixed' === $dueType) {
            $dueDate = $request->request->get('dueDate');
            if ($dueDate) {
                try {
                    $dateTime = new \DateTimeImmutable($dueDate);
                    $task->setDueDate($dateTime);
                    $task->setDueDaysFromEntry(null);
                } catch (\Exception $e) {
                    // Log the error or handle it gracefully
                    $task->setDueDate(null);
                    $task->setDueDaysFromEntry(null);
                }
            } else {
                $task->setDueDate(null);
                $task->setDueDaysFromEntry(null);
            }
        } elseif ('relative' === $dueType) {
            $days = $request->request->get('dueDaysFromEntry');
            if (null !== $days && '' !== $days) {
                $int = (int) $days;
                $task->setDueDaysFromEntry($int);
                $entryDate = $onboarding->getEntryDate();
                if ($entryDate) {
                    $task->setDueDate((clone $entryDate)->modify(sprintf('%+d days', $int)));
                } else {
                    $task->setDueDate(null);
                }
            } else {
                $task->setDueDate(null);
                $task->setDueDaysFromEntry(null);
            }
        } else {
            $task->setDueDate(null);
            $task->setDueDaysFromEntry(null);
        }
    }

    /**
     * Wendet Zuweisungen aus Request an.
     */
    private function applyAssignmentFromRequest(OnboardingTask $task, Request $request): void
    {
        $roleId = $request->request->get('assignedRole');
        if ($roleId) {
            $role = $this->entityManager->getRepository(Role::class)->find($roleId);
            if ($role) {
                $task->setAssignedRole($role);
            }
        } else {
            $task->setAssignedRole(null);
        }

        $email = $request->request->get('assignedEmail');
        $task->setAssignedEmail($email ?: null);
    }

    /**
     * Wendet E-Mail-Konfiguration aus Request an.
     */
    private function applyEmailFromRequest(OnboardingTask $task, Request $request): void
    {
        if ($request->request->get('sendEmail')) {
            $task->setSendEmail(true);
            $task->setEmailTemplate($request->request->get('emailTemplate'));
        } else {
            $task->setSendEmail(false);
            $task->setEmailTemplate(null);
        }
    }
}
