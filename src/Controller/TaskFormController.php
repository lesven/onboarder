<?php

namespace App\Controller;

use App\Entity\Onboarding;
use App\Entity\OnboardingTask;
use App\Entity\Role;
use App\Service\OnboardingTaskFacade;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Controller für Task-Formular-Management (CRUD für einzelne Tasks).
 */
#[Route('/onboarding/{onboardingId}/task')]
class TaskFormController extends AbstractController
{
    public function __construct(private readonly OnboardingTaskFacade $taskService)
    {
    }

    #[Route('/add', name: 'app_onboarding_add_task')]
    public function add(int $onboardingId, Request $request, EntityManagerInterface $entityManager): Response
    {
        $onboarding = $entityManager->getRepository(Onboarding::class)->find($onboardingId);
        if (!$onboarding) {
            throw $this->createNotFoundException('Onboarding nicht gefunden');
        }

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

    #[Route('/{id}/edit', name: 'app_onboarding_task_edit')]
    public function edit(int $onboardingId, OnboardingTask $task, Request $request, EntityManagerInterface $entityManager): Response
    {
        // Sicherheitscheck: Task gehört zum richtigen Onboarding
        if ($task->getOnboarding()->getId() !== $onboardingId) {
            throw $this->createNotFoundException('Task gehört nicht zu diesem Onboarding');
        }

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
}
