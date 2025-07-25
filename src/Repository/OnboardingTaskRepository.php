<?php

namespace App\Repository;

use App\Entity\OnboardingTask;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<OnboardingTask>
 */
class OnboardingTaskRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OnboardingTask::class);
    }

    /**
     * Findet alle überfälligen Tasks.
     */
    public function findOverdueTasks(): array
    {
        return $this->createQueryBuilder('ot')
            ->leftJoin('ot.onboarding', 'o')
            ->where('ot.status != :status')
            ->andWhere('ot.dueDate < :today OR (ot.dueDaysFromEntry IS NOT NULL AND DATE_ADD(o.entryDate, ot.dueDaysFromEntry, \'DAY\') < :today)')
            ->setParameter('status', OnboardingTask::STATUS_COMPLETED)
            ->setParameter('today', new \DateTimeImmutable())
            ->orderBy('ot.dueDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Findet alle Tasks für ein bestimmtes Onboarding.
     */
    public function findByOnboarding(int $onboardingId): array
    {
        return $this->createQueryBuilder('ot')
            ->leftJoin('ot.assignedRole', 'r')
            ->leftJoin('ot.taskBlock', 'tb')
            ->addSelect('r', 'tb')
            ->where('ot.onboarding = :onboardingId')
            ->setParameter('onboardingId', $onboardingId)
            ->orderBy('ot.sortOrder', 'ASC')
            ->addOrderBy('ot.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Zählt offene Tasks für ein Onboarding.
     */
    public function countOpenTasksForOnboarding(int $onboardingId): int
    {
        return $this->createQueryBuilder('ot')
            ->select('COUNT(ot.id)')
            ->where('ot.onboarding = :onboardingId')
            ->andWhere('ot.status != :status')
            ->setParameter('onboardingId', $onboardingId)
            ->setParameter('status', OnboardingTask::STATUS_COMPLETED)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Findet alle Tasks, deren E-Mail heute versendet werden soll.
     * Berücksichtigt sowohl heute fällige als auch überfällige Tasks, die noch nicht versendet wurden.
     */
    public function findTasksDueForDate(\DateTimeImmutable $date): array
    {
        $start = $date->setTime(0, 0);
        $end = $start->modify('+1 day');

        return $this->createQueryBuilder('ot')
            ->leftJoin('ot.onboarding', 'o')
            ->where('ot.sendEmail = true')
            ->andWhere('ot.emailTemplate IS NOT NULL')
            ->andWhere('ot.emailSentAt IS NULL')
            ->andWhere('(
                (ot.dueDate IS NOT NULL AND ot.dueDate < :end) OR
                (ot.dueDate IS NULL AND ot.dueDaysFromEntry IS NOT NULL AND DATE_ADD(o.entryDate, ot.dueDaysFromEntry, \'DAY\') < :end)
            )')
            ->setParameter('end', $end)
            ->orderBy('ot.dueDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findOneByCompletionToken(string $token): ?OnboardingTask
    {
        return $this->createQueryBuilder('ot')
            ->where('ot.completionToken = :token')
            ->setParameter('token', $token)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
