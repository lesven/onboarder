<?php

namespace App\Controller;

use App\Entity\BaseType;
use App\Entity\Onboarding;
use App\Entity\OnboardingType;
use App\Entity\Task;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DashboardController extends AbstractController
{
    #[Route('/', name: 'app_dashboard')]
    public function index(EntityManagerInterface $entityManager): Response
    {
        // Alle aktiven Onboardings laden
        $onboardings = $entityManager->getRepository(Onboarding::class)
            ->findBy([], ['createdAt' => 'DESC'], 10);

        // Gesamtzahl der Onboardings
        $totalOnboardings = $entityManager->getRepository(Onboarding::class)
            ->createQueryBuilder('o')
            ->select('COUNT(o.id)')
            ->getQuery()
            ->getSingleScalarResult();

        // Aktive Onboardings (mit offenen Aufgaben)
        $activeOnboardings = $entityManager->getRepository(Onboarding::class)
            ->createQueryBuilder('o')
            ->select('COUNT(DISTINCT o.id)')
            ->leftJoin('o.tasks', 't')
            ->where('t.status != :status OR t.status IS NULL')
            ->setParameter('status', Task::STATUS_COMPLETED)
            ->getQuery()
            ->getSingleScalarResult();

        // Offene Aufgaben zählen
        $openTasks = $entityManager->getRepository(Task::class)
            ->createQueryBuilder('t')
            ->select('COUNT(t.id)')
            ->where('t.status != :status')
            ->setParameter('status', Task::STATUS_COMPLETED)
            ->getQuery()
            ->getSingleScalarResult();

        // Überfällige Aufgaben
        $overdueTasks = $entityManager->getRepository(Task::class)
            ->createQueryBuilder('t')
            ->where('t.dueDate < :today')
            ->andWhere('t.status != :status')
            ->setParameter('today', new \DateTimeImmutable())
            ->setParameter('status', Task::STATUS_COMPLETED)
            ->orderBy('t.dueDate', 'ASC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();

        // Gesamtzahl der OnboardingTypes
        $totalTypes = $entityManager->getRepository(OnboardingType::class)
            ->createQueryBuilder('ot')
            ->select('COUNT(ot.id)')
            ->getQuery()
            ->getSingleScalarResult();

        // Gesamtzahl der BaseTypes
        $totalBaseTypes = $entityManager->getRepository(BaseType::class)
            ->createQueryBuilder('bt')
            ->select('COUNT(bt.id)')
            ->getQuery()
            ->getSingleScalarResult();

        // Statistiken für das Template
        $stats = [
            'total_onboardings' => $totalOnboardings,
            'active_onboardings' => $activeOnboardings,
            'open_tasks' => $openTasks,
            'overdue_tasks' => count($overdueTasks),
            'total_types' => $totalTypes,
            'total_base_types' => $totalBaseTypes,
        ];

        return $this->render('dashboard/index.html.twig', [
            'onboardings' => $onboardings,
            'stats' => $stats,
            'overdueTasks' => $overdueTasks,
        ]);
    }

    #[Route('/onboarding/{id}', name: 'app_onboarding_detail')]
    public function onboardingDetail(Onboarding $onboarding): Response
    {
        return $this->render('dashboard/onboarding_detail.html.twig', [
            'onboarding' => $onboarding,
            'tasks' => $onboarding->getTasks(),
        ]);
    }

    #[Route('/tasks', name: 'app_tasks_overview')]
    public function tasksOverview(EntityManagerInterface $entityManager): Response
    {
        // Alle Aufgaben mit Filter-Optionen
        $tasks = $entityManager->getRepository(Task::class)
            ->createQueryBuilder('t')
            ->leftJoin('t.onboarding', 'o')
            ->leftJoin('t.taskBlock', 'tb')
            ->leftJoin('t.assignedRole', 'r')
            ->addSelect('o', 'tb', 'r')
            ->orderBy('t.dueDate', 'ASC')
            ->getQuery()
            ->getResult();

        return $this->render('dashboard/tasks_overview.html.twig', [
            'tasks' => $tasks,
        ]);
    }
}
