<?php

namespace App\Controller;

use App\Entity\BaseType;
use App\Entity\OnboardingType;
use App\Entity\Role;
use App\Entity\Task;
use App\Entity\TaskBlock;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin')]
class AdminController extends AbstractController
{
    #[Route('/', name: 'app_admin_dashboard')]
    public function index(EntityManagerInterface $entityManager): Response
    {
        $stats = [
            'baseTypes' => $entityManager->getRepository(BaseType::class)->count([]),
            'onboardingTypes' => $entityManager->getRepository(OnboardingType::class)->count([]),
            'roles' => $entityManager->getRepository(Role::class)->count([]),
            'taskBlocks' => $entityManager->getRepository(TaskBlock::class)->count([]),
        ];

        return $this->render('admin/index.html.twig', [
            'stats' => $stats,
        ]);
    }

    #[Route('/base-types', name: 'app_admin_base_types')]
    public function baseTypes(EntityManagerInterface $entityManager): Response
    {
        $baseTypes = $entityManager->getRepository(BaseType::class)->findAll();

        return $this->render('admin/base_types.html.twig', [
            'baseTypes' => $baseTypes,
        ]);
    }

    #[Route('/onboarding-types', name: 'app_admin_onboarding_types')]
    public function onboardingTypes(EntityManagerInterface $entityManager): Response
    {
        $onboardingTypes = $entityManager->getRepository(OnboardingType::class)->findAll();

        return $this->render('admin/onboarding_types.html.twig', [
            'onboardingTypes' => $onboardingTypes,
        ]);
    }

    #[Route('/roles', name: 'app_admin_roles')]
    public function roles(EntityManagerInterface $entityManager): Response
    {
        $roles = $entityManager->getRepository(Role::class)->findAll();

        return $this->render('admin/roles.html.twig', [
            'roles' => $roles,
        ]);
    }

    #[Route('/task-blocks', name: 'app_admin_task_blocks')]
    public function taskBlocks(EntityManagerInterface $entityManager): Response
    {
        $taskBlocks = $entityManager->getRepository(TaskBlock::class)->findAll();

        return $this->render('admin/task_blocks.html.twig', [
            'taskBlocks' => $taskBlocks,
        ]);
    }

    #[Route('/base-type/new', name: 'app_admin_base_type_new')]
    public function newBaseType(Request $request, EntityManagerInterface $entityManager): Response
    {
        if ($request->isMethod('POST')) {
            $baseType = new BaseType();
            $baseType->setName($request->request->get('name'));
            $baseType->setDescription($request->request->get('description'));

            $entityManager->persist($baseType);
            $entityManager->flush();

            $this->addFlash('success', 'BaseType wurde erfolgreich erstellt!');
            return $this->redirectToRoute('app_admin_base_types');
        }

        return $this->render('admin/base_type_form.html.twig');
    }

    #[Route('/role/new', name: 'app_admin_role_new')]
    public function newRole(Request $request, EntityManagerInterface $entityManager): Response
    {
        if ($request->isMethod('POST')) {
            $role = new Role();
            $role->setName($request->request->get('name'));
            $role->setEmail($request->request->get('email'));
            $role->setDescription($request->request->get('description'));

            $entityManager->persist($role);
            $entityManager->flush();

            $this->addFlash('success', 'Rolle wurde erfolgreich erstellt!');
            return $this->redirectToRoute('app_admin_roles');
        }

        return $this->render('admin/role_form.html.twig');
    }

    #[Route('/onboarding-type/new', name: 'app_admin_onboarding_type_new')]
    public function newOnboardingType(Request $request, EntityManagerInterface $entityManager): Response
    {
        if ($request->isMethod('POST')) {
            $onboardingType = new OnboardingType();
            $onboardingType->setName($request->request->get('name'));
            $onboardingType->setDescription($request->request->get('description'));
            
            // BaseType zuweisen falls ausgewählt
            $baseTypeId = $request->request->get('baseType');
            if ($baseTypeId) {
                $baseType = $entityManager->getRepository(BaseType::class)->find($baseTypeId);
                if ($baseType) {
                    $onboardingType->setBaseType($baseType);
                }
            }

            $entityManager->persist($onboardingType);
            $entityManager->flush();

            $this->addFlash('success', 'OnboardingType wurde erfolgreich erstellt!');
            return $this->redirectToRoute('app_admin_onboarding_types');
        }

        // BaseTypes für das Formular laden
        $baseTypes = $entityManager->getRepository(BaseType::class)->findAll();

        return $this->render('admin/onboarding_type_form.html.twig', [
            'baseTypes' => $baseTypes,
        ]);
    }

    #[Route('/onboarding-type/{id}', name: 'app_admin_onboarding_type_show')]
    public function showOnboardingType(OnboardingType $onboardingType): Response
    {
        return $this->render('admin/onboarding_type_show.html.twig', [
            'onboardingType' => $onboardingType,
        ]);
    }

    #[Route('/onboarding-type/{id}/edit', name: 'app_admin_onboarding_type_edit')]
    public function editOnboardingType(Request $request, OnboardingType $onboardingType, EntityManagerInterface $entityManager): Response
    {
        if ($request->isMethod('POST')) {
            $onboardingType->setName($request->request->get('name'));
            $onboardingType->setDescription($request->request->get('description'));

            $baseTypeId = $request->request->get('baseType');
            $baseType = null;
            if ($baseTypeId) {
                $baseType = $entityManager->getRepository(BaseType::class)->find($baseTypeId);
            }
            $onboardingType->setBaseType($baseType);
            $onboardingType->setUpdatedAt(new \DateTimeImmutable());

            $entityManager->flush();

            $this->addFlash('success', 'OnboardingType wurde erfolgreich aktualisiert!');
            return $this->redirectToRoute('app_admin_onboarding_types');
        }

        $baseTypes = $entityManager->getRepository(BaseType::class)->findAll();

        return $this->render('admin/onboarding_type_form.html.twig', [
            'onboardingType' => $onboardingType,
            'baseTypes' => $baseTypes,
        ]);
    }

    #[Route('/onboarding-type/{id}/delete', name: 'app_admin_onboarding_type_delete')]
    public function deleteOnboardingType(OnboardingType $onboardingType, EntityManagerInterface $entityManager): Response
    {
        $entityManager->remove($onboardingType);
        $entityManager->flush();

        $this->addFlash('success', 'OnboardingType wurde erfolgreich gelöscht!');
        return $this->redirectToRoute('app_admin_onboarding_types');
    }

    #[Route('/admin/task-blocks/new', name: 'app_admin_new_task_block', methods: ['GET', 'POST'])]
    public function newTaskBlock(Request $request, EntityManagerInterface $entityManager): Response
    {
        if ($request->isMethod('POST')) {
            $taskBlock = new TaskBlock();
            $taskBlock->setName($request->request->get('name'));
            $taskBlock->setDescription($request->request->get('description'));
            $taskBlock->setSortOrder((int)$request->request->get('sortOrder') ?: 0);
            
            // BaseType optional zuordnen
            $baseTypeId = $request->request->get('baseType');
            if ($baseTypeId) {
                $baseType = $entityManager->getRepository(BaseType::class)->find($baseTypeId);
                if ($baseType) {
                    $taskBlock->setBaseType($baseType);
                }
            }
            
            $entityManager->persist($taskBlock);
            $entityManager->flush();
            
            $this->addFlash('success', 'TaskBlock wurde erfolgreich erstellt.');
            return $this->redirectToRoute('app_admin_task_blocks');
        }
        
        // BaseTypes für das Dropdown laden
        $baseTypes = $entityManager->getRepository(BaseType::class)->findAll();
        
        return $this->render('admin/task_block_form.html.twig', [
            'baseTypes' => $baseTypes
        ]);
    }

    #[Route('/admin/task-blocks/{id}', name: 'app_admin_task_block_show', requirements: ['id' => '\d+'])]
    public function showTaskBlock(TaskBlock $taskBlock): Response
    {
        return $this->render('admin/task_block_show.html.twig', [
            'taskBlock' => $taskBlock
        ]);
    }

    #[Route('/task-blocks/{id}/edit', name: 'app_admin_task_block_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function editTaskBlock(TaskBlock $taskBlock, Request $request, EntityManagerInterface $entityManager): Response
    {
        if ($request->isMethod('POST')) {
            $taskBlock->setName($request->request->get('name'));
            $taskBlock->setDescription($request->request->get('description'));
            $taskBlock->setSortOrder((int)$request->request->get('sortOrder') ?: 0);
            
            // BaseType optional zuordnen
            $baseTypeId = $request->request->get('baseType');
            if ($baseTypeId) {
                $baseType = $entityManager->getRepository(BaseType::class)->find($baseTypeId);
                $taskBlock->setBaseType($baseType);
            } else {
                $taskBlock->setBaseType(null);
            }
            
            $entityManager->flush();
            
            $this->addFlash('success', 'TaskBlock wurde erfolgreich aktualisiert.');
            return $this->redirectToRoute('app_admin_task_blocks');
        }
        
        // BaseTypes für das Dropdown laden
        $baseTypes = $entityManager->getRepository(BaseType::class)->findAll();
        
        return $this->render('admin/task_block_edit.html.twig', [
            'taskBlock' => $taskBlock,
            'baseTypes' => $baseTypes
        ]);
    }

    #[Route('/task-blocks/{id}/delete', name: 'app_admin_task_block_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function deleteTaskBlock(TaskBlock $taskBlock, EntityManagerInterface $entityManager): Response
    {
        $entityManager->remove($taskBlock);
        $entityManager->flush();
        
        $this->addFlash('success', 'TaskBlock wurde erfolgreich gelöscht.');
        return $this->redirectToRoute('app_admin_task_blocks');
    }

    #[Route('/task-blocks/{id}/tasks', name: 'app_admin_task_block_tasks', requirements: ['id' => '\d+'])]
    public function taskBlockTasks(TaskBlock $taskBlock, EntityManagerInterface $entityManager): Response
    {
        return $this->render('admin/task_block_tasks.html.twig', [
            'taskBlock' => $taskBlock
        ]);
    }

    #[Route('/task-blocks/{id}/tasks/new', name: 'app_admin_task_block_new_task', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function newTaskForBlock(TaskBlock $taskBlock, Request $request, EntityManagerInterface $entityManager): Response
    {
        if ($request->isMethod('POST')) {
            $task = new Task();
            $task->setTitle($request->request->get('title'));
            $task->setDescription($request->request->get('description'));
            $task->setSortOrder((int)$request->request->get('sortOrder') ?: 0);
            $task->setTaskBlock($taskBlock);
            
            // Fälligkeit konfigurieren
            $dueDateType = $request->request->get('dueDateType');
            if ($dueDateType === 'fixed') {
                $dueDate = $request->request->get('dueDate');
                if ($dueDate) {
                    $task->setDueDate(new \DateTimeImmutable($dueDate));
                }
            } elseif ($dueDateType === 'relative') {
                $dueDays = $request->request->get('dueDaysFromEntry');
                if ($dueDays !== null && $dueDays !== '') {
                    $task->setDueDaysFromEntry((int)$dueDays);
                }
            }
            
            // Zuständigkeit
            $assignedEmail = $request->request->get('assignedEmail');
            if ($assignedEmail) {
                $task->setAssignedEmail($assignedEmail);
            }
            
            $assignedRoleId = $request->request->get('assignedRole');
            if ($assignedRoleId) {
                $role = $entityManager->getRepository(Role::class)->find($assignedRoleId);
                if ($role) {
                    $task->setAssignedRole($role);
                }
            }
            
            // E-Mail-Konfiguration
            $sendEmail = $request->request->get('sendEmail');
            if ($sendEmail) {
                $task->setEmailTemplate($request->request->get('emailTemplate'));
                // E-Mail wird zur Fälligkeit der Task versendet (kein separates Datum)
            } else {
                $task->setEmailTemplate(null);
            }
            
            $entityManager->persist($task);
            $entityManager->flush();
            
            $this->addFlash('success', 'Task wurde erfolgreich erstellt.');
            return $this->redirectToRoute('app_admin_task_block_tasks', ['id' => $taskBlock->getId()]);
        }
        
        // Rollen für das Dropdown laden
        $roles = $entityManager->getRepository(Role::class)->findAll();
        
        return $this->render('admin/task_form.html.twig', [
            'taskBlock' => $taskBlock,
            'roles' => $roles,
            'task' => null
        ]);
    }

    #[Route('/task-blocks/{id}/tasks/{taskId}/edit', name: 'app_admin_task_edit', requirements: ['id' => '\d+', 'taskId' => '\d+'], methods: ['GET', 'POST'])]
    public function editTask(TaskBlock $taskBlock, int $taskId, Request $request, EntityManagerInterface $entityManager): Response
    {
        $task = $entityManager->getRepository(Task::class)->find($taskId);
        if (!$task || $task->getTaskBlock()->getId() !== $taskBlock->getId()) {
            throw $this->createNotFoundException('Task not found');
        }
        if ($request->isMethod('POST')) {
            $task->setTitle($request->request->get('title'));
            $task->setDescription($request->request->get('description'));
            $task->setSortOrder((int)$request->request->get('sortOrder') ?: 0);
            
            // Fälligkeit konfigurieren
            $dueDateType = $request->request->get('dueDateType');
            if ($dueDateType === 'fixed') {
                $dueDate = $request->request->get('dueDate');
                if ($dueDate) {
                    $task->setDueDate(new \DateTimeImmutable($dueDate));
                    $task->setDueDaysFromEntry(null);
                }
            } elseif ($dueDateType === 'relative') {
                $dueDays = $request->request->get('dueDaysFromEntry');
                if ($dueDays !== null && $dueDays !== '') {
                    $task->setDueDaysFromEntry((int)$dueDays);
                    $task->setDueDate(null);
                }
            } else {
                $task->setDueDate(null);
                $task->setDueDaysFromEntry(null);
            }
            
            // Zuständigkeit zurücksetzen
            $task->setAssignedEmail(null);
            $task->setAssignedRole(null);
            
            $assignedEmail = $request->request->get('assignedEmail');
            if ($assignedEmail) {
                $task->setAssignedEmail($assignedEmail);
            }
            
            $assignedRoleId = $request->request->get('assignedRole');
            if ($assignedRoleId) {
                $role = $entityManager->getRepository(Role::class)->find($assignedRoleId);
                if ($role) {
                    $task->setAssignedRole($role);
                }
            }
            
            // E-Mail-Konfiguration
            $sendEmail = $request->request->get('sendEmail');
            if ($sendEmail) {
                $task->setEmailTemplate($request->request->get('emailTemplate'));
            } else {
                $task->setEmailTemplate(null);
            }
            
            $entityManager->flush();
            
            $this->addFlash('success', 'Task wurde erfolgreich aktualisiert.');
            return $this->redirectToRoute('app_admin_task_block_tasks', ['id' => $taskBlock->getId()]);
        }
        
        // Rollen für das Dropdown laden
        $roles = $entityManager->getRepository(Role::class)->findAll();
        
        return $this->render('admin/task_form.html.twig', [
            'taskBlock' => $taskBlock,
            'roles' => $roles,
            'task' => $task
        ]);
    }

    #[Route('/task-blocks/{id}/tasks/{taskId}/delete', name: 'app_admin_task_delete', requirements: ['id' => '\d+', 'taskId' => '\d+'], methods: ['POST'])]
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
