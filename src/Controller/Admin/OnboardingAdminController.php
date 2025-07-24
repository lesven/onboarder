<?php

namespace App\Controller\Admin;

use App\Entity\BaseType;
use App\Entity\OnboardingType;
use App\Entity\Role;
use App\Entity\Task;
use App\Entity\TaskBlock;
use App\Service\AdminLookupService;
use App\Service\TaskService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Handles administration of task blocks and template tasks.
 */
#[Route('/admin')]
class OnboardingAdminController extends AbstractController
{
    public function __construct(private readonly AdminLookupService $lookup)
    {
    }

    #[Route('/task-blocks', name: 'app_admin_task_blocks')]
    public function taskBlocks(EntityManagerInterface $entityManager): Response
    {
        $taskBlocks = $entityManager->getRepository(TaskBlock::class)->findAll();

        return $this->render('admin/task_blocks.html.twig', [
            'taskBlocks' => $taskBlocks,
        ]);
    }

    #[Route('/task-blocks/new', name: 'app_admin_new_task_block', methods: ['GET', 'POST'])]
    public function newTaskBlock(Request $request, EntityManagerInterface $entityManager): Response
    {
        if ($request->isMethod('POST')) {
            $taskBlock = new TaskBlock();
            $taskBlock->setName($request->request->get('name'));
            $taskBlock->setDescription($request->request->get('description'));
            $taskBlock->setSortOrder((int) $request->request->get('sortOrder') ?: 0);

            // BaseType optional zuordnen
            $baseTypeId = $request->request->get('baseType');
            if ($baseTypeId) {
                $baseType = $entityManager->getRepository(BaseType::class)->find($baseTypeId);
                if ($baseType) {
                    $taskBlock->setBaseType($baseType);
                }
            }

            // OnboardingType optional zuordnen
            $onboardingTypeId = $request->request->get('onboardingType');
            if ($onboardingTypeId) {
                $onboardingType = $entityManager->getRepository(OnboardingType::class)->find($onboardingTypeId);
                if ($onboardingType) {
                    $taskBlock->setOnboardingType($onboardingType);
                }
            }

            $entityManager->persist($taskBlock);
            $entityManager->flush();

            $this->addFlash('success', 'TaskBlock wurde erfolgreich erstellt.');

            return $this->redirectToRoute('app_admin_task_blocks');
        }

        return $this->render('admin/task_block_form.html.twig', [
            'baseTypes' => $this->lookup->getBaseTypes(),
            'onboardingTypes' => $this->lookup->getOnboardingTypes(),
        ]);
    }

    #[Route('/admin/task-blocks/{id}', name: 'app_admin_task_block_show', requirements: ['id' => '\\d+'])]
    public function showTaskBlock(TaskBlock $taskBlock): Response
    {
        return $this->render('admin/task_block_show.html.twig', [
            'taskBlock' => $taskBlock,
        ]);
    }

    #[Route('/admin/task-blocks/{id}/edit', name: 'app_admin_task_block_edit', requirements: ['id' => '\\d+'], methods: ['GET', 'POST'])]
    public function editTaskBlock(TaskBlock $taskBlock, Request $request, EntityManagerInterface $entityManager): Response
    {
        if ($request->isMethod('POST')) {
            $taskBlock->setName($request->request->get('name'));
            $taskBlock->setDescription($request->request->get('description'));
            $taskBlock->setSortOrder((int) $request->request->get('sortOrder') ?: 0);

            // BaseType optional zuordnen
            $baseTypeId = $request->request->get('baseType');
            if ($baseTypeId) {
                $baseType = $entityManager->getRepository(BaseType::class)->find($baseTypeId);
                $taskBlock->setBaseType($baseType);
            } else {
                $taskBlock->setBaseType(null);
            }

            // OnboardingType optional zuordnen
            $onboardingTypeId = $request->request->get('onboardingType');
            if ($onboardingTypeId) {
                $onboardingType = $entityManager->getRepository(OnboardingType::class)->find($onboardingTypeId);
                $taskBlock->setOnboardingType($onboardingType);
            } else {
                $taskBlock->setOnboardingType(null);
            }

            $entityManager->flush();

            $this->addFlash('success', 'TaskBlock wurde erfolgreich aktualisiert.');

            return $this->redirectToRoute('app_admin_task_blocks');
        }

        return $this->render('admin/task_block_edit.html.twig', [
            'taskBlock' => $taskBlock,
            'baseTypes' => $this->lookup->getBaseTypes(),
            'onboardingTypes' => $this->lookup->getOnboardingTypes(),
        ]);
    }

    #[Route('/task-blocks/{id}/delete', name: 'app_admin_task_block_delete', requirements: ['id' => '\\d+'], methods: ['POST'])]
    public function deleteTaskBlock(TaskBlock $taskBlock, EntityManagerInterface $entityManager): Response
    {
        $entityManager->remove($taskBlock);
        $entityManager->flush();

        $this->addFlash('success', 'TaskBlock wurde erfolgreich gelöscht.');

        return $this->redirectToRoute('app_admin_task_blocks');
    }

    #[Route('/task-blocks/{id}/tasks', name: 'app_admin_task_block_tasks', requirements: ['id' => '\\d+'])]
    public function taskBlockTasks(TaskBlock $taskBlock): Response
    {
        return $this->render('admin/task_block_tasks.html.twig', [
            'taskBlock' => $taskBlock,
        ]);
    }

    #[Route('/task-blocks/{id}/tasks/new', name: 'app_admin_task_block_new_task', requirements: ['id' => '\\d+'], methods: ['GET', 'POST'])]
    public function newTaskForBlock(TaskBlock $taskBlock, Request $request, EntityManagerInterface $entityManager, TaskService $taskService): Response
    {
        if ($request->isMethod('POST')) {
            $taskService->createTask($taskBlock, $request);

            $this->addFlash('success', 'Task wurde erfolgreich erstellt.');

            return $this->redirectToRoute('app_admin_task_block_tasks', ['id' => $taskBlock->getId()]);
        }

        return $this->render('admin/task_form.html.twig', [
            'taskBlock' => $taskBlock,
            'roles' => $this->lookup->getRoles(),
            'task' => null,
        ]);
    }

    #[Route('/task-blocks/{id}/tasks/{taskId}/edit', name: 'app_admin_task_edit', requirements: ['id' => '\\d+', 'taskId' => '\\d+'], methods: ['GET', 'POST'])]
    public function editTask(TaskBlock $taskBlock, int $taskId, Request $request, EntityManagerInterface $entityManager, TaskService $taskService): Response
    {
        $task = $entityManager->getRepository(Task::class)->find($taskId);
        if (!$task || $task->getTaskBlock()->getId() !== $taskBlock->getId()) {
            throw $this->createNotFoundException('Task not found');
        }
        if ($request->isMethod('POST')) {
            $taskService->updateTask($task, $request);

            $this->addFlash('success', 'Task wurde erfolgreich aktualisiert.');

            return $this->redirectToRoute('app_admin_task_block_tasks', ['id' => $taskBlock->getId()]);
        }

        return $this->render('admin/task_form.html.twig', [
            'taskBlock' => $taskBlock,
            'roles' => $this->lookup->getRoles(),
            'task' => $task,
        ]);
    }

    #[Route('/task-blocks/{id}/tasks/{taskId}/delete', name: 'app_admin_task_delete', requirements: ['id' => '\\d+', 'taskId' => '\\d+'], methods: ['POST'])]
    public function deleteTask(TaskBlock $taskBlock, int $taskId, EntityManagerInterface $entityManager): Response
    {
        $task = $entityManager->getRepository(Task::class)->find($taskId);
        if (!$task || $task->getTaskBlock()->getId() !== $taskBlock->getId()) {
            throw $this->createNotFoundException('Task not found');
        }

        $entityManager->remove($task);
        $entityManager->flush();

        $this->addFlash('success', 'Task wurde erfolgreich gelöscht.');

        return $this->redirectToRoute('app_admin_task_block_tasks', ['id' => $taskBlock->getId()]);
    }
}
