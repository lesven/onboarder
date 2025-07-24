<?php

namespace App\Controller;

use App\Entity\BaseType;
use App\Entity\Onboarding;
use App\Entity\OnboardingTask;
use App\Entity\OnboardingType;
use App\Entity\Role;
use App\Entity\Task;
use App\Service\OnboardingTaskService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DashboardController extends AbstractController
{
    public function __construct(private readonly OnboardingTaskService $taskService)
    {
    }
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
            ->leftJoin('o.onboardingTasks', 'ot')
            ->where('ot.status != :status OR ot.status IS NULL')
            ->setParameter('status', OnboardingTask::STATUS_COMPLETED)
            ->getQuery()
            ->getSingleScalarResult();

        // Offene Aufgaben zählen
        $openTasks = $entityManager->getRepository(OnboardingTask::class)
            ->createQueryBuilder('ot')
            ->select('COUNT(ot.id)')
            ->where('ot.status != :status')
            ->setParameter('status', OnboardingTask::STATUS_COMPLETED)
            ->getQuery()
            ->getSingleScalarResult();

        // Überfällige Aufgaben
        $overdueTasks = $entityManager->getRepository(OnboardingTask::class)
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

            // Tasks aus TaskBlocks generieren
            $this->taskService->generateForOnboarding($onboarding);

            $this->addFlash('success', 'Onboarding für '.$onboarding->getFullName().' wurde erfolgreich erstellt!');

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
        // OnboardingTasks nach TaskBlocks gruppieren
        $tasksByBlock = [];
        foreach ($onboarding->getOnboardingTasks() as $task) {
            $blockName = $task->getTaskBlock() ? $task->getTaskBlock()->getName() : 'Sonderaufgaben';
            if (!isset($tasksByBlock[$blockName])) {
                $tasksByBlock[$blockName] = [];
            }
            $tasksByBlock[$blockName][] = $task;
        }

        // Nach Block-Namen sortieren (alphabetisch)
        ksort($tasksByBlock);

        return $this->render('dashboard/onboarding_detail.html.twig', [
            'onboarding' => $onboarding,
            'tasks' => $onboarding->getOnboardingTasks(), // Für Statistiken
            'tasksByBlock' => $tasksByBlock,
        ]);
    }

    #[Route('/onboarding/task/{id}/toggle-complete', name: 'app_task_toggle_complete', methods: ['POST'])]
    public function toggleTaskComplete(OnboardingTask $task, Request $request): Response
    {
        [$type, $message] = $this->taskService->toggleStatus($task);

        $this->addFlash($type, $message);

        // Zurück zur ursprünglichen Seite oder Onboarding-Detail als Fallback
        $referer = $request->headers->get('referer');
        if ($referer && str_contains($referer, '/tasks')) {
            return $this->redirectToRoute('app_tasks_overview');
        }

        return $this->redirectToRoute('app_onboarding_detail', ['id' => $task->getOnboarding()->getId()]);
    }

    #[Route('/tasks', name: 'app_tasks_overview')]
    public function tasksOverview(EntityManagerInterface $entityManager, Request $request): Response
    {
        $statusFilter = $request->query->get('status', '');
        $employeeFilter = $request->query->get('employee', '');
        $assigneeFilter = $request->query->get('assignee', '');

        $tasks = $this->taskService->getFilteredTasks($statusFilter, $employeeFilter, $assigneeFilter);

        return $this->render('dashboard/tasks_overview.html.twig', [
            'tasks' => $tasks,
        ]);
    }

    #[Route('/admin', name: 'app_admin')]
    public function adminRedirect(): Response
    {
        return $this->redirectToRoute('app_admin_dashboard');
    }

    #[Route('/onboarding/{id}/delete', name: 'app_onboarding_delete', methods: ['POST'])]
    public function deleteOnboarding(Onboarding $onboarding, EntityManagerInterface $entityManager): Response
    {
        $fullName = $onboarding->getFullName();

        // Onboarding löschen (OnboardingTasks werden automatisch durch cascade gelöscht)
        $entityManager->remove($onboarding);
        $entityManager->flush();

        $this->addFlash('success', 'Onboarding für '.$fullName.' wurde erfolgreich gelöscht!');

        return $this->redirectToRoute('app_onboardings');
    }

    #[Route('/onboarding/{id}/add-task', name: 'app_onboarding_add_task')]
    public function addOnboardingTask(Onboarding $onboarding, Request $request, EntityManagerInterface $entityManager): Response
    {
        if ($request->isMethod('POST')) {
            $this->taskService->createOnboardingTask($onboarding, $request);

            $this->addFlash('success', 'Aufgabe hinzugefügt.');

            return $this->redirectToRoute('app_onboarding_detail', ['id' => $onboarding->getId()]);
        }

        $roles = $entityManager->getRepository(Role::class)->findAll();

        return $this->render('dashboard/onboarding_task_form.html.twig', [
            'onboarding' => $onboarding,
            'roles' => $roles,
            'task' => null,
        ]);
    }

    #[Route('/onboarding/task/{id}/edit', name: 'app_onboarding_task_edit')]
    public function editOnboardingTask(OnboardingTask $task, Request $request, EntityManagerInterface $entityManager): Response
    {
        $onboarding = $task->getOnboarding();

        if ($request->isMethod('POST')) {
            $this->taskService->updateOnboardingTask($task, $request);

            $this->addFlash('success', 'Aufgabe aktualisiert.');

            return $this->redirectToRoute('app_onboarding_detail', ['id' => $onboarding->getId()]);
        }

        $roles = $entityManager->getRepository(Role::class)->findAll();

        return $this->render('dashboard/onboarding_task_form.html.twig', [
            'onboarding' => $onboarding,
            'roles' => $roles,
            'task' => $task,
        ]);
    }

    #[Route('/onboarding/task/{id}/delete', name: 'app_onboarding_task_delete', methods: ['POST'])]
    public function deleteOnboardingTask(OnboardingTask $task, EntityManagerInterface $entityManager): Response
    {
        $onboardingId = $task->getOnboarding()->getId();

        $entityManager->remove($task);
        $entityManager->flush();

        $this->addFlash('success', 'Aufgabe gelöscht.');

        return $this->redirectToRoute('app_onboarding_detail', ['id' => $onboardingId]);
    }

}
