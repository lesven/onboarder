<?php

namespace App\Service;

use App\Entity\Role;
use App\Entity\Task;
use App\Entity\TaskBlock;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;

class TaskService
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function createTask(TaskBlock $taskBlock, Request $request): Task
    {
        $task = new Task();
        $task->setTaskBlock($taskBlock);
        $this->populateTask($task, $request);

        $this->entityManager->persist($task);
        $this->entityManager->flush();

        return $task;
    }

    public function updateTask(Task $task, Request $request): Task
    {
        $this->populateTask($task, $request);
        $this->entityManager->flush();

        return $task;
    }

    private function populateTask(Task $task, Request $request): void
    {
        $task->setTitle($request->request->get('title'));
        $task->setDescription($request->request->get('description'));
        $task->setSortOrder((int) $request->request->get('sortOrder') ?: 0);

        $dueDateType = $request->request->get('dueDateType');
        if ('fixed' === $dueDateType) {
            $dueDate = $request->request->get('dueDate');
            if ($dueDate) {
                $dateTime = \DateTimeImmutable::createFromFormat('Y-m-d', $dueDate);
                if ($dateTime === false) {
                    // Handle invalid date format (e.g., log an error or set to null)
                    $task->setDueDate(null);
                } else {
                    $task->setDueDate($dateTime);
                }
            } else {
                $task->setDueDate(null);
            }
            $task->setDueDaysFromEntry(null);
        } elseif ('relative' === $dueDateType) {
            $dueDays = $request->request->get('dueDaysFromEntry');
            $task->setDueDaysFromEntry(null !== $dueDays && '' !== $dueDays ? (int) $dueDays : null);
            $task->setDueDate(null);
        } else {
            $task->setDueDate(null);
            $task->setDueDaysFromEntry(null);
        }

        // Reset assignments
        $task->setAssignedEmail(null);
        $task->setAssignedRole(null);

        $assignedEmail = $request->request->get('assignedEmail');
        if ($assignedEmail) {
            $task->setAssignedEmail($assignedEmail);
        }

        $assignedRoleId = $request->request->get('assignedRole');
        if ($assignedRoleId) {
            $role = $this->entityManager->getRepository(Role::class)->find($assignedRoleId);
            if ($role) {
                $task->setAssignedRole($role);
            }
        }

        $sendEmail = $request->request->get('sendEmail');
        if ($sendEmail) {
            $task->setEmailTemplate($request->request->get('emailTemplate'));
        } else {
            $task->setEmailTemplate(null);
        }
    }
}
