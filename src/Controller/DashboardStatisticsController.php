<?php

namespace App\Controller;

use App\Entity\BaseType;
use App\Entity\Onboarding;
use App\Entity\OnboardingTask;
use App\Entity\OnboardingType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Controller für das Haupt-Dashboard mit Statistiken und Übersichten.
 */
class DashboardStatisticsController extends AbstractController
{
    #[Route('/', name: 'app_dashboard')]
    public function index(EntityManagerInterface $entityManager): Response
    {
        // Alle aktiven Onboardings laden
        $onboardings = $entityManager->getRepository(Onboarding::class)
            ->findBy([], ['createdAt' => 'DESC'], 10);

        // Statistiken sammeln
        $stats = $this->collectDashboardStats($entityManager);
        
        // Überfällige Aufgaben für Dashboard-Widget
        $overdueTasks = $this->getOverdueTasks($entityManager);

        return $this->render('dashboard/index.html.twig', [
            'onboardings' => $onboardings,
            'stats' => $stats,
            'overdueTasks' => $overdueTasks,
        ]);
    }

    /**
     * Sammelt alle Dashboard-Statistiken.
     */
    private function collectDashboardStats(EntityManagerInterface $entityManager): array
    {
        return [
            'total_onboardings' => $this->getTotalOnboardings($entityManager),
            'active_onboardings' => $this->getActiveOnboardings($entityManager),
            'open_tasks' => $this->getOpenTasks($entityManager),
            'overdue_tasks' => count($this->getOverdueTasks($entityManager)),
            'total_types' => $this->getTotalOnboardingTypes($entityManager),
            'total_base_types' => $this->getTotalBaseTypes($entityManager),
        ];
    }

    /**
     * Gesamtzahl aller Onboardings.
     */
    private function getTotalOnboardings(EntityManagerInterface $entityManager): int
    {
        return $entityManager->getRepository(Onboarding::class)
            ->createQueryBuilder('o')
            ->select('COUNT(o.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Anzahl aktiver Onboardings (mit offenen Aufgaben).
     */
    private function getActiveOnboardings(EntityManagerInterface $entityManager): int
    {
        return $entityManager->getRepository(Onboarding::class)
            ->createQueryBuilder('o')
            ->select('COUNT(DISTINCT o.id)')
            ->leftJoin('o.onboardingTasks', 'ot')
            ->where('ot.status != :status OR ot.status IS NULL')
            ->setParameter('status', OnboardingTask::STATUS_COMPLETED)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Anzahl offener Aufgaben.
     */
    private function getOpenTasks(EntityManagerInterface $entityManager): int
    {
        return $entityManager->getRepository(OnboardingTask::class)
            ->createQueryBuilder('ot')
            ->select('COUNT(ot.id)')
            ->where('ot.status != :status')
            ->setParameter('status', OnboardingTask::STATUS_COMPLETED)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Top 5 überfällige Aufgaben für Dashboard-Widget.
     */
    private function getOverdueTasks(EntityManagerInterface $entityManager): array
    {
        return $entityManager->getRepository(OnboardingTask::class)
            ->createQueryBuilder('ot')
            ->leftJoin('ot.onboarding', 'o')
            ->addSelect('o')
            ->where('ot.dueDate < :today')
            ->andWhere('ot.status != :status')
            ->setParameter('today', new \DateTimeImmutable())
            ->setParameter('status', OnboardingTask::STATUS_COMPLETED)
            ->orderBy('ot.dueDate', 'ASC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();
    }

    /**
     * Gesamtzahl der OnboardingTypes.
     */
    private function getTotalOnboardingTypes(EntityManagerInterface $entityManager): int
    {
        return $entityManager->getRepository(OnboardingType::class)
            ->createQueryBuilder('ot')
            ->select('COUNT(ot.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Gesamtzahl der BaseTypes.
     */
    private function getTotalBaseTypes(EntityManagerInterface $entityManager): int
    {
        return $entityManager->getRepository(BaseType::class)
            ->createQueryBuilder('bt')
            ->select('COUNT(bt.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }
}
