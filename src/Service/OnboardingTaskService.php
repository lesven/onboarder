<?php

namespace App\Service;

use App\Entity\Onboarding;
use App\Entity\OnboardingTask;
use App\Entity\Role;
use App\Entity\Task;
use App\Entity\TaskBlock;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpFoundation\Request;

/**
 * Service class responsible for creating and manipulating {@link OnboardingTask} entities.
 *
 * The service encapsulates the business logic used when generating tasks for an
 * {@link Onboarding}, updating their due dates, or handling user supplied data
 * from HTTP requests. All database interaction is performed via the injected
 * {@link EntityManagerInterface}.
 */
class OnboardingTaskService
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    /**
     * Generiert OnboardingTask-Entitäten für das übergebene Onboarding.
     *
     * Alle Tasks, die im OnboardingType und dessen optionalem BaseType definiert sind,
     * werden erstellt und persistiert. Duplikate von TaskBlocks werden herausgefiltert,
     * um das doppelte Generieren derselben Tasks zu vermeiden.
     */
    public function generateForOnboarding(Onboarding $onboarding): void
    {
        foreach ($this->collectTaskBlocks($onboarding) as $block) {
            foreach ($block->getTasks() as $template) {
                $task = $this->createTaskFromTemplate($template, $onboarding, $block);
                $this->entityManager->persist($task);
            }
        }

        $this->entityManager->flush();
    }

    /**
     * Sammelt alle eindeutigen TaskBlock-Instanzen für den OnboardingType.
     *
     * Kombiniert TaskBlocks aus dem direkten OnboardingType und dessen BaseType,
     * wobei Duplikate automatisch herausgefiltert werden.
     *
     * @return TaskBlock[]
     */
    private function collectTaskBlocks(Onboarding $onboarding): array
    {
        $type = $onboarding->getOnboardingType();
        if (!$type) {
            return [];
        }

        $blocks = [];
        $processed = [];

        $add = static function (TaskBlock $block) use (&$blocks, &$processed): void {
            if (!in_array($block->getId(), $processed, true)) {
                $blocks[] = $block;
                $processed[] = $block->getId();
            }
        };

        foreach ($type->getTaskBlocks() as $block) {
            $add($block);
        }

        if ($base = $type->getBaseType()) {
            foreach ($base->getTaskBlocks() as $block) {
                $add($block);
            }
        }

        return $blocks;
    }

    /**
     * Erstellt eine OnboardingTask aus einer Template-Task.
     *
     * Kopiert alle relevanten Eigenschaften von der Template-Task und
     * wendet spezifische Onboarding-Daten wie Fälligkeitsdaten an.
     */
    private function createTaskFromTemplate(Task $template, Onboarding $onboarding, TaskBlock $block): OnboardingTask
    {
        $task = new OnboardingTask();
        $task->setTitle($template->getTitle());
        $task->setDescription($template->getDescription());
        $task->setSortOrder($template->getSortOrder());
        $task->setTaskBlock($block);
        $task->setTemplateTask($template);
        $task->setOnboarding($onboarding);

        $this->applyTemplateDueDate($task, $template, $onboarding->getEntryDate());
        $this->applyTemplateAssignments($task, $template);
        $this->applyTemplateEmail($task, $template);

        return $task;
    }

    /**
     * Wendet das Fälligkeitsdatum aus der Template-Task auf die OnboardingTask an.
     *
     * Behandelt sowohl absolute Fälligkeitsdaten als auch relative Daten
     * basierend auf dem Eintrittsdatum des Mitarbeiters.
     */
    private function applyTemplateDueDate(OnboardingTask $task, Task $template, ?\DateTimeImmutable $entryDate): void
    {
        if ($template->getDueDate()) {
            $task->setDueDate($template->getDueDate());

            return;
        }

        if (null === $template->getDueDaysFromEntry()) {
            return;
        }

        $days = $template->getDueDaysFromEntry();
        if ($entryDate) {
            $task->setDueDate((clone $entryDate)->modify(sprintf('%+d days', $days)));
        }

        $task->setDueDaysFromEntry($days);
    }

    /**
     * Wendet Zuweisungseinstellungen aus der Template-Task an.
     *
     * Überträgt sowohl rollenbasierte als auch direkte E-Mail-Zuweisungen.
     */
    private function applyTemplateAssignments(OnboardingTask $task, Task $template): void
    {
        if ($template->getAssignedRole()) {
            $task->setAssignedRole($template->getAssignedRole());
        }

        if ($template->getAssignedEmail()) {
            $task->setAssignedEmail($template->getAssignedEmail());
        }
    }

    /**
     * Wendet E-Mail-Konfiguration aus der Template-Task an.
     *
     * Aktiviert automatisch E-Mail-Versand, wenn ein E-Mail-Template vorhanden ist.
     */
    private function applyTemplateEmail(OnboardingTask $task, Task $template): void
    {
        if ($template->getEmailTemplate()) {
            $task->setEmailTemplate($template->getEmailTemplate());
            $task->setSendEmail(true);
        }
    }

    /**
     * Wendet Statusfilter auf den QueryBuilder an.
     *
     * Unterstützt Filter für 'completed', 'overdue', 'all' und Standard (offene Tasks).
     */
    private function applyStatusFilter(QueryBuilder $qb, string $status): void
    {
        if ('completed' === $status) {
            $qb->where('ot.status = :completed')
               ->setParameter('completed', OnboardingTask::STATUS_COMPLETED);

            return;
        }

        if ('overdue' === $status) {
            $qb->where('ot.status != :completed AND ot.dueDate < :now')
               ->setParameter('completed', OnboardingTask::STATUS_COMPLETED)
               ->setParameter('now', new \DateTimeImmutable());

            return;
        }

        if ('all' !== $status) {
            $qb->where('ot.status != :completed')
               ->setParameter('completed', OnboardingTask::STATUS_COMPLETED);
        }
    }

    /**
     * Berechnet relative Fälligkeitsdaten nach Änderung des Eintrittsdatums neu.
     *
     * Durchläuft alle OnboardingTasks und aktualisiert nur die Tasks mit
     * relativen Fälligkeitsdaten basierend auf dem neuen Eintrittsdatum.
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
     *
     * Nur Tasks mit relativen Fälligkeitsdaten werden aktualisiert.
     */
    private function updateTaskDueDate(OnboardingTask $task, \DateTimeImmutable $entryDate): void
    {
        if (null === $task->getDueDaysFromEntry()) {
            return;
        }

        $task->setDueDate((clone $entryDate)->modify(sprintf('%+d days', $task->getDueDaysFromEntry())));
        $task->setUpdatedAt(new \DateTimeImmutable());
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

        $this->applyStatusFilter($qb, $status);

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
     * Erstellt eine neue OnboardingTask mit Daten aus dem Request.
     *
     * Die Task wird sofort persistiert und zurückgegeben.
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
     * Aktualisiert eine bestehende OnboardingTask mit Daten aus dem Request.
     *
     * Setzt das updatedAt-Feld und persistiert die Änderungen.
     */
    public function updateOnboardingTask(OnboardingTask $task, Request $request): OnboardingTask
    {
        $this->populateFromRequest($task, $task->getOnboarding(), $request);
        $task->setUpdatedAt(new \DateTimeImmutable());
        $this->entityManager->flush();

        return $task;
    }

    /**
     * Befüllt eine OnboardingTask mit Daten aus dem HTTP-Request.
     *
     * Zentrale Methode zur Verarbeitung aller Task-Eigenschaften aus Formulardaten.
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
     * Wendet Fälligkeitsdatum-Einstellungen aus dem Request an.
     *
     * Unterstützt drei Modi: 'none', 'fixed' (absolutes Datum) und 'relative' (Tage ab Eintritt).
     */
    private function applyDueDateFromRequest(OnboardingTask $task, Onboarding $onboarding, Request $request): void
    {
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

            return;
        }

        if ('relative' === $dueType) {
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

            return;
        }

        $task->setDueDate(null);
        $task->setDueDaysFromEntry(null);
    }

    /**
     * Wendet Zuweisungseinstellungen aus dem Request an.
     *
     * Behandelt sowohl rollenbasierte als auch direkte E-Mail-Zuweisungen.
     */
    private function applyAssignmentFromRequest(OnboardingTask $task, Request $request): void
    {
        $roleId = $request->request->get('assignedRole');
        if ($roleId) {
            $role = $this->entityManager->getRepository(Role::class)->find($roleId);
            $task->setAssignedRole($role);
        } else {
            $task->setAssignedRole(null);
        }

        $email = $request->request->get('assignedEmail');
        $task->setAssignedEmail($email ?: null);
    }

    /**
     * Wendet E-Mail-Konfiguration aus dem Request an.
     *
     * Aktiviert E-Mail-Versand und setzt das Template, falls entsprechende Daten vorhanden sind.
     */
    private function applyEmailFromRequest(OnboardingTask $task, Request $request): void
    {
        if ($request->request->get('sendEmail')) {
            $task->setSendEmail(true);
            $task->setEmailTemplate($request->request->get('emailTemplate'));

            return;
        }

        $task->setSendEmail(false);
        $task->setEmailTemplate(null);
    }
}
