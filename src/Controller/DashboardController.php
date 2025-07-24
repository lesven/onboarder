<?php

namespace App\Controller;

use App\Entity\BaseType;
use App\Entity\Onboarding;
use App\Entity\OnboardingType;
use App\Entity\Task;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
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

    #[Route('/onboarding/new', name: 'app_onboarding_new')]
    public function newOnboarding(Request $request, EntityManagerInterface $entityManager): Response
    {
        if ($request->isMethod('POST')) {
            $onboarding = new Onboarding();
            $onboarding->setFirstName($request->request->get('firstName'));
            $onboarding->setLastName($request->request->get('lastName'));
            $onboarding->setEntryDate(new \DateTimeImmutable($request->request->get('entryDate')));
            $onboarding->setPosition($request->request->get('position'));
            $onboarding->setTeam($request->request->get('team'));
            $onboarding->setManager($request->request->get('manager'));
            $onboarding->setManagerEmail($request->request->get('managerEmail'));
            $onboarding->setBuddy($request->request->get('buddy'));
            $onboarding->setBuddyEmail($request->request->get('buddyEmail'));
            
            // OnboardingType zuweisen falls ausgewählt
            $onboardingTypeId = $request->request->get('onboardingType');
            if ($onboardingTypeId) {
                $onboardingType = $entityManager->getRepository(OnboardingType::class)->find($onboardingTypeId);
                if ($onboardingType) {
                    $onboarding->setOnboardingType($onboardingType);
                }
            }

            $entityManager->persist($onboarding);
            $entityManager->flush();

            $this->addFlash('success', 'Onboarding für ' . $onboarding->getFullName() . ' wurde erfolgreich erstellt!');
            return $this->redirectToRoute('app_onboardings');
        }

        // OnboardingTypes für das Formular laden
        $onboardingTypes = $entityManager->getRepository(OnboardingType::class)->findAll();

        return $this->render('dashboard/onboarding_form.html.twig', [
            'onboardingTypes' => $onboardingTypes,
        ]);
    }

    #[Route('/onboardings', name: 'app_onboardings')]
    public function onboardings(EntityManagerInterface $entityManager): Response
    {
        // Alle Onboardings laden
        $onboardings = $entityManager->getRepository(Onboarding::class)
            ->findBy([], ['createdAt' => 'DESC']);

        return $this->render('dashboard/onboardings.html.twig', [
            'onboardings' => $onboardings,
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

    #[Route('/admin', name: 'app_admin')]
    public function adminRedirect(): Response
    {
        return $this->redirectToRoute('app_admin_dashboard');
    }
}
