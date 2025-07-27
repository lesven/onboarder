<?php

namespace App\Service;

use App\Entity\OnboardingTask;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use DateTimeImmutable;

/**
 * Verantwortlich für das Abfragen und Filtern von OnboardingTasks.
 */
class TaskQueryService
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    /**
     * Liefert gefilterte Aufgaben für die Übersicht.
     *
     * @return OnboardingTask[]
     */
    public function getFilteredTasks(string $status = '', string $employee = '', string $assignee = ''): array
    {
        $queryBuilder = $this->entityManager->getRepository(OnboardingTask::class)
            ->createQueryBuilder('ot')
            ->leftJoin('ot.onboarding', 'o')
            ->leftJoin('ot.taskBlock', 'tb')
            ->leftJoin('ot.assignedRole', 'r')
            ->addSelect('o', 'tb', 'r');

        $this->applyStatusFilter($queryBuilder, $status);
        $this->applyEmployeeFilter($queryBuilder, $employee);
        $this->applyAssigneeFilter($queryBuilder, $assignee);

        return $queryBuilder->orderBy('ot.dueDate', 'ASC')
            ->addOrderBy('ot.sortOrder', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Wendet Statusfilter an.
     */
    private function applyStatusFilter(QueryBuilder $queryBuilder, string $status): void
    {
        if ('completed' === $status) {
            $queryBuilder->where('ot.status = :completed')
               ->setParameter('completed', OnboardingTask::STATUS_COMPLETED);
        } elseif ('overdue' === $status) {
            $queryBuilder->where('ot.status != :completed AND ot.dueDate < :now')
               ->setParameter('completed', OnboardingTask::STATUS_COMPLETED)
               ->setParameter('now', new DateTimeImmutable());
        } elseif ('all' !== $status) {
            $queryBuilder->where('ot.status != :completed')
               ->setParameter('completed', OnboardingTask::STATUS_COMPLETED);
        }
    }

    /**
     * Wendet Mitarbeiterfilter an.
     */
    private function applyEmployeeFilter(QueryBuilder $queryBuilder, string $employee): void
    {
        if ($employee) {
            $queryBuilder->andWhere('o.firstName LIKE :employee OR o.lastName LIKE :employee')
               ->setParameter('employee', '%'.$employee.'%');
        }
    }

    /**
     * Wendet Zuständigkeitsfilter an.
     */
    private function applyAssigneeFilter(QueryBuilder $queryBuilder, string $assignee): void
    {
        if ($assignee) {
            $queryBuilder->andWhere('r.name LIKE :assignee OR ot.assignedEmail LIKE :assignee')
               ->setParameter('assignee', '%'.$assignee.'%');
        }
    }
}
