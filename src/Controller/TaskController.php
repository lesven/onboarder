<?php

namespace App\Controller;

use App\Entity\OnboardingTask;
use App\Service\OnboardingTaskFacade;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Controller für Task-Management und Task-Übersichten.
 */
#[Route('/tasks')]
class TaskController extends AbstractController
{
    public function __construct(private readonly OnboardingTaskFacade $taskService)
    {
    }

    #[Route('', name: 'app_tasks_overview')]
    public function overview(Request $request): Response
    {
        // Filter aus Query oder Cookie ermitteln
        $statusFilter = $request->query->get('status');
        $employeeFilter = $request->query->get('employee');
        $assigneeFilter = $request->query->get('assignee');

        if ($request->query->has('reset')) {
            $statusFilter = $employeeFilter = $assigneeFilter = '';
        } else {
            $statusFilter ??= $request->cookies->get('tasks_status', '');
            $employeeFilter ??= $request->cookies->get('tasks_employee', '');
            $assigneeFilter ??= $request->cookies->get('tasks_assignee', '');
        }

        $tasks = $this->taskService->getFilteredTasks($statusFilter, $employeeFilter, $assigneeFilter);

        $response = $this->render('dashboard/tasks_overview.html.twig', [
            'tasks' => $tasks,
            'statusFilter' => $statusFilter,
            'employeeFilter' => $employeeFilter,
            'assigneeFilter' => $assigneeFilter,
        ]);

        if ($request->query->has('reset')) {
            $response->headers->setCookie(new Cookie('tasks_status', '', 0));
            $response->headers->setCookie(new Cookie('tasks_employee', '', 0));
            $response->headers->setCookie(new Cookie('tasks_assignee', '', 0));
        } else {
            $expires = strtotime('+1 year');
            $response->headers->setCookie(new Cookie('tasks_status', $statusFilter, $expires));
            $response->headers->setCookie(new Cookie('tasks_employee', $employeeFilter, $expires));
            $response->headers->setCookie(new Cookie('tasks_assignee', $assigneeFilter, $expires));
        }

        return $response;
    }

    #[Route('/{id}/toggle-complete', name: 'app_task_toggle_complete', methods: ['POST'])]
    public function toggleComplete(OnboardingTask $task, Request $request): Response
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

    #[Route('/{id}/delete', name: 'app_onboarding_task_delete', methods: ['POST'])]
    public function delete(OnboardingTask $task, EntityManagerInterface $entityManager): Response
    {
        $onboardingId = $task->getOnboarding()->getId();

        $entityManager->remove($task);
        $entityManager->flush();

        $this->addFlash('success', 'Aufgabe gelöscht.');

        return $this->redirectToRoute('app_onboarding_detail', ['id' => $onboardingId]);
    }
}
