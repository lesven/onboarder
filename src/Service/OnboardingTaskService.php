<?php

namespace App\Service;

use App\Entity\Onboarding;
use App\Entity\OnboardingTask;
use App\Entity\Role;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Beinhaltet Hilfsfunktionen für OnboardingTasks.
 */
class OnboardingTaskService
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    /**
     * Erzeugt OnboardingTasks basierend auf dem zugewiesenen OnboardingType.
     */
    public function generateForOnboarding(Onboarding $onboarding): void
    {
        $onboardingType = $onboarding->getOnboardingType();
        if (!$onboardingType) {
            return;
        }

        $taskBlocks = [];
        $processedIds = [];

        foreach ($onboardingType->getTaskBlocks() as $block) {
            if (!in_array($block->getId(), $processedIds, true)) {
                $taskBlocks[] = $block;
                $processedIds[] = $block->getId();
            }
        }

        if ($onboardingType->getBaseType()) {
            foreach ($onboardingType->getBaseType()->getTaskBlocks() as $block) {
                if (!in_array($block->getId(), $processedIds, true)) {
                    $taskBlocks[] = $block;
                    $processedIds[] = $block->getId();
                }
            }
        }

        foreach ($taskBlocks as $block) {
            foreach ($block->getTasks() as $templateTask) {
                $task = new OnboardingTask();
                $task->setTitle($templateTask->getTitle());
                $task->setDescription($templateTask->getDescription());
                $task->setSortOrder($templateTask->getSortOrder());
                $task->setTaskBlock($block);
                $task->setTemplateTask($templateTask);
                $task->setOnboarding($onboarding);

                if ($templateTask->getDueDate()) {
                    $task->setDueDate($templateTask->getDueDate());
                } elseif (null !== $templateTask->getDueDaysFromEntry()) {
                    $entryDate = $onboarding->getEntryDate();
                    if ($entryDate) {
                        $days = $templateTask->getDueDaysFromEntry();
                        $task->setDueDate((clone $entryDate)->modify(sprintf('%+d days', $days)));
                        $task->setDueDaysFromEntry($days);
                    } else {
                        $task->setDueDaysFromEntry($templateTask->getDueDaysFromEntry());
                    }
                }

                if ($templateTask->getAssignedRole()) {
                    $task->setAssignedRole($templateTask->getAssignedRole());
                }
                if ($templateTask->getAssignedEmail()) {
                    $task->setAssignedEmail($templateTask->getAssignedEmail());
                }

                if ($templateTask->getEmailTemplate()) {
                    $task->setEmailTemplate($templateTask->getEmailTemplate());
                    $task->setSendEmail(true);
                }

                $this->entityManager->persist($task);
            }
        }

        $this->entityManager->flush();
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
            if (null !== $task->getDueDaysFromEntry()) {
                $task->setDueDate((clone $entryDate)->modify(sprintf('%+d days', $task->getDueDaysFromEntry())));
                $task->setUpdatedAt(new \DateTimeImmutable());
            }
        }

        $this->entityManager->flush();
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
     * Liefert gefilterte Aufgaben für die Übersicht.
     *
     * @return OnboardingTask[]
     */
    public function getFilteredTasks(string $status = '', string $employee = '', string $assignee = ''): array
    {
        $qb = $this->entityManager->getRepository(OnboardingTask::class)
            ->createQueryBuilder('ot')
            ->leftJoin('ot.onboarding', 'o')
            ->leftJoin('ot.taskBlock', 'tb')
            ->leftJoin('ot.assignedRole', 'r')
            ->addSelect('o', 'tb', 'r');

        if ('completed' === $status) {
            $qb->where('ot.status = :completed')
               ->setParameter('completed', OnboardingTask::STATUS_COMPLETED);
        } elseif ('overdue' === $status) {
            $qb->where('ot.status != :completed AND ot.dueDate < :now')
               ->setParameter('completed', OnboardingTask::STATUS_COMPLETED)
               ->setParameter('now', new \DateTimeImmutable());
        } elseif ('all' !== $status) {
            $qb->where('ot.status != :completed')
               ->setParameter('completed', OnboardingTask::STATUS_COMPLETED);
        }

        if ($employee) {
            $qb->andWhere('o.firstName LIKE :employee OR o.lastName LIKE :employee')
               ->setParameter('employee', '%'.$employee.'%');
        }

        if ($assignee) {
            $qb->andWhere('r.name LIKE :assignee OR ot.assignedEmail LIKE :assignee')
               ->setParameter('assignee', '%'.$assignee.'%');
        }

        return $qb->orderBy('ot.dueDate', 'ASC')
            ->addOrderBy('ot.sortOrder', 'ASC')
            ->getQuery()
            ->getResult();
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

    private function populateFromRequest(OnboardingTask $task, Onboarding $onboarding, Request $request): void
    {
        $task->setTitle($request->request->get('title'));
        $task->setDescription($request->request->get('description'));
        $task->setSortOrder((int) ($request->request->get('sortOrder') ?: 0));

        $dueType = $request->request->get('dueDateType', 'none');
        if ('fixed' === $dueType) {
            $dueDate = $request->request->get('dueDate');
            if ($dueDate) {
                $task->setDueDate(new \DateTimeImmutable($dueDate));
                $task->setDueDaysFromEntry(null);
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
                    $task->setDueDate($entryDate->modify(sprintf('%+d days', $int)));
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

        if ($request->request->get('sendEmail')) {
            $task->setSendEmail(true);
            $task->setEmailTemplate($request->request->get('emailTemplate'));
        } else {
            $task->setSendEmail(false);
            $task->setEmailTemplate(null);
        }
    }
}
