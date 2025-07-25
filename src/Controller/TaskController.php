<?php

namespace App\Controller;

use App\Entity\OnboardingTask;
use App\Service\AdminLookupService;
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
    private const COOKIE_EXPIRATION_PERIOD = '+1 year';
    private const COOKIE_STATUS = 'tasks_status';
    private const COOKIE_EMPLOYEE = 'tasks_employee';
    private const COOKIE_ASSIGNEE = 'tasks_assignee';

    public function __construct(
        private readonly OnboardingTaskFacade $taskService,
        private readonly AdminLookupService $lookup
    ) {
    }

    #[Route('', name: 'app_tasks_overview')]
    public function overview(Request $request): Response
    {
        // Filter aus Query oder Cookie ermitteln und Cookies entsprechend setzen
        $statusFilter = $request->query->get('status');
        $employeeFilter = $request->query->get('employee');
        $assigneeFilter = $request->query->get('assignee');

        if ($request->query->has('reset')) {
            // Reset: Filter zurücksetzen und Cookies löschen
            $statusFilter = $employeeFilter = $assigneeFilter = '';
            $cookieExpires = 0;
        } else {
            // Normal: Filter aus Request oder Cookies lesen
            $statusFilter ??= $request->cookies->get('tasks_status', '');
            $employeeFilter ??= $request->cookies->get('tasks_employee', '');
            $assigneeFilter ??= $request->cookies->get('tasks_assignee', '');
            $cookieExpires = strtotime(self::COOKIE_EXPIRATION_PERIOD);
        }

        $tasks = $this->taskService->getFilteredTasks($statusFilter, $employeeFilter, $assigneeFilter);
        $roles = $this->lookup->getRoles();

        $response = $this->render('dashboard/tasks_overview.html.twig', [
            'tasks' => $tasks,
            'statusFilter' => $statusFilter,
            'employeeFilter' => $employeeFilter,
            'assigneeFilter' => $assigneeFilter,
            'roles' => $roles,
        ]);

        // Cookies mit entsprechenden Werten und Ablaufzeit setzen
        $response->headers->setCookie(new Cookie(self::COOKIE_STATUS, $statusFilter, $cookieExpires));
        $response->headers->setCookie(new Cookie(self::COOKIE_EMPLOYEE, $employeeFilter, $cookieExpires));
        $response->headers->setCookie(new Cookie(self::COOKIE_ASSIGNEE, $assigneeFilter, $cookieExpires));

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
