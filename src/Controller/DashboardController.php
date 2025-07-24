<?php

namespace App\Controller;

use App\Entity\BaseType;
use App\Entity\Onboarding;
use App\Entity\OnboardingType;
use App\Entity\OnboardingTask;
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

            // Tasks aus TaskBlocks generieren
            $this->generateTasksForOnboarding($onboarding, $entityManager);

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
        // OnboardingTasks nach TaskBlocks gruppieren
        $tasksByBlock = [];
        foreach ($onboarding->getOnboardingTasks() as $task) {
            $blockName = $task->getTaskBlock() ? $task->getTaskBlock()->getName() : 'Ohne Block';
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
    public function toggleTaskComplete(OnboardingTask $task, EntityManagerInterface $entityManager): Response
    {
        // Status umschalten
        if ($task->getStatus() === OnboardingTask::STATUS_COMPLETED) {
            $task->setStatus(OnboardingTask::STATUS_PENDING);
            $task->setCompletedAt(null);
            $message = 'Aufgabe "' . $task->getTitle() . '" wurde als ausstehend markiert.';
            $type = 'info';
        } else {
            $task->markAsCompleted();
            $message = 'Aufgabe "' . $task->getTitle() . '" wurde als erledigt markiert.';
            $type = 'success';
        }

        $entityManager->flush();

        $this->addFlash($type, $message);
        return $this->redirectToRoute('app_onboarding_detail', ['id' => $task->getOnboarding()->getId()]);
    }

    #[Route('/tasks', name: 'app_tasks_overview')]
    public function tasksOverview(EntityManagerInterface $entityManager): Response
    {
        // Alle OnboardingTasks mit Filter-Optionen laden
        $tasks = $entityManager->getRepository(OnboardingTask::class)
            ->createQueryBuilder('ot')
            ->leftJoin('ot.onboarding', 'o')
            ->leftJoin('ot.taskBlock', 'tb')
            ->leftJoin('ot.assignedRole', 'r')
            ->addSelect('o', 'tb', 'r')
            ->orderBy('ot.dueDate', 'ASC')
            ->addOrderBy('ot.sortOrder', 'ASC')
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

    #[Route('/onboarding/{id}/delete', name: 'app_onboarding_delete', methods: ['POST'])]
    public function deleteOnboarding(Onboarding $onboarding, EntityManagerInterface $entityManager): Response
    {
        $fullName = $onboarding->getFullName();
        
        // Onboarding löschen (OnboardingTasks werden automatisch durch cascade gelöscht)
        $entityManager->remove($onboarding);
        $entityManager->flush();

        $this->addFlash('success', 'Onboarding für ' . $fullName . ' wurde erfolgreich gelöscht!');
        return $this->redirectToRoute('app_onboardings');
    }

    /**
     * Generiert OnboardingTasks für ein Onboarding basierend auf seinem OnboardingType
     */
    private function generateTasksForOnboarding(Onboarding $onboarding, EntityManagerInterface $entityManager): void
    {
        $onboardingType = $onboarding->getOnboardingType();
        if (!$onboardingType) {
            return; // Kein OnboardingType zugeordnet
        }

        // Alle TaskBlocks sammeln und deduplizieren (TaskBlocks sind die Vorlagen)
        $taskBlocks = [];
        $processedTaskBlockIds = []; // Um TaskBlock-Duplikate zu vermeiden
        
        // 1. TaskBlocks die direkt dem OnboardingType zugeordnet sind
        foreach ($onboardingType->getTaskBlocks() as $taskBlock) {
            if (!in_array($taskBlock->getId(), $processedTaskBlockIds)) {
                $taskBlocks[] = $taskBlock;
                $processedTaskBlockIds[] = $taskBlock->getId();
            }
        }
        
        // 2. TaskBlocks die dem BaseType zugeordnet sind (falls vorhanden)
        if ($onboardingType->getBaseType()) {
            foreach ($onboardingType->getBaseType()->getTaskBlocks() as $taskBlock) {
                // Nur hinzufügen wenn TaskBlock noch nicht verarbeitet wurde
                if (!in_array($taskBlock->getId(), $processedTaskBlockIds)) {
                    $taskBlocks[] = $taskBlock;
                    $processedTaskBlockIds[] = $taskBlock->getId();
                }
            }
        }

        // OnboardingTasks aus allen TaskBlocks erstellen (Tasks hängen direkt am Onboarding)
        foreach ($taskBlocks as $taskBlock) {
            foreach ($taskBlock->getTasks() as $templateTask) {
                // Neue OnboardingTask-Instanz für dieses Onboarding erstellen
                $onboardingTask = new OnboardingTask();
                $onboardingTask->setTitle($templateTask->getTitle());
                $onboardingTask->setDescription($templateTask->getDescription());
                $onboardingTask->setSortOrder($templateTask->getSortOrder());
                $onboardingTask->setTaskBlock($taskBlock); // Referenz zur Vorlage
                $onboardingTask->setTemplateTask($templateTask); // Referenz zur Template-Task
                $onboardingTask->setOnboarding($onboarding); // Task hängt direkt am Onboarding
                
                // Fälligkeit basierend auf Template und Eintrittsdatum berechnen
                if ($templateTask->getDueDate()) {
                    // Absolute Fälligkeit - direkt übernehmen
                    $onboardingTask->setDueDate($templateTask->getDueDate());
                } elseif ($templateTask->getDueDaysFromEntry() !== null) {
                    // Relative Fälligkeit - basierend auf Eintrittsdatum berechnen
                    $entryDate = $onboarding->getEntryDate();
                    if ($entryDate) {
                        // DateTimeImmutable modify erstellt eine neue Instanz
                        $daysFromEntry = $templateTask->getDueDaysFromEntry();
                        $effectiveDueDate = $entryDate->modify(sprintf('%+d days', $daysFromEntry));
                        $onboardingTask->setDueDate($effectiveDueDate);
                        $onboardingTask->setDueDaysFromEntry($daysFromEntry); // Für Referenz behalten
                        
                        // Debug: Überprüfe ob das Datum korrekt gesetzt wurde
                        error_log(sprintf('Task "%s": Entry Date = %s, Days = %d, Due Date = %s', 
                            $templateTask->getTitle(),
                            $entryDate->format('Y-m-d'),
                            $daysFromEntry,
                            $effectiveDueDate->format('Y-m-d')
                        ));
                    } else {
                        // Fallback: nur relative Tage setzen falls kein Eintrittsdatum vorhanden
                        $onboardingTask->setDueDaysFromEntry($templateTask->getDueDaysFromEntry());
                    }
                }
                
                // Zuständigkeit übernehmen
                if ($templateTask->getAssignedRole()) {
                    $onboardingTask->setAssignedRole($templateTask->getAssignedRole());
                }
                if ($templateTask->getAssignedEmail()) {
                    $onboardingTask->setAssignedEmail($templateTask->getAssignedEmail());
                }
                
                // E-Mail-Konfiguration aus der neuen vereinfachten Struktur übernehmen
                if ($templateTask->getEmailTemplate()) {
                    $onboardingTask->setEmailTemplate($templateTask->getEmailTemplate());
                    $onboardingTask->setSendEmail(true); // E-Mail aktivieren wenn Template vorhanden
                }
                
                $entityManager->persist($onboardingTask);
            }
        }
        
        $entityManager->flush();
    }

    /**
     * Aktualisiert die Fälligkeitsdaten aller OnboardingTasks eines Onboardings 
     * basierend auf dem aktuellen Eintrittsdatum
     */
    private function updateTaskDueDates(Onboarding $onboarding, EntityManagerInterface $entityManager): void
    {
        $entryDate = $onboarding->getEntryDate();
        if (!$entryDate) {
            return; // Kein Eintrittsdatum verfügbar
        }

        foreach ($onboarding->getOnboardingTasks() as $onboardingTask) {
            // Nur Tasks mit relativen Fälligkeiten aktualisieren
            if ($onboardingTask->getDueDaysFromEntry() !== null) {
                $effectiveDueDate = $entryDate->modify(sprintf('%+d days', $onboardingTask->getDueDaysFromEntry()));
                $onboardingTask->setDueDate($effectiveDueDate);
                $onboardingTask->setUpdatedAt(new \DateTimeImmutable());
            }
        }
        
        $entityManager->flush();
    }
}
