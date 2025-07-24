<?php

namespace App\Service;

use App\Entity\Role;
use App\Entity\Task;
use App\Entity\TaskBlock;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Service class responsible for managing Task entities.
 *
 * This class provides methods to create, update, and populate Task objects
 * based on data from HTTP requests. It interacts with the database via the
 * EntityManagerInterface to persist and retrieve related entities such as Role.
 *
 * Responsibilities:
 * - Create new Task entities and associate them with TaskBlock.
 * - Update existing Task entities with new data.
 * - Populate Task entities with data from HTTP requests, including handling
 *   due dates, assignments, and email templates.
 *
 * Dependencies:
 * - EntityManagerInterface: Used for database operations.
 */
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
