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

    /**
     * Creates a new Task entity, associates it with the given TaskBlock, and populates its properties
     * based on the provided Request object. Persists the Task to the database.
     *
     * @param TaskBlock $taskBlock the TaskBlock entity to associate with the new Task
     * @param Request   $request   the HTTP request containing data to populate the Task
     *
     * @return Task the newly created Task entity
     *
     * @throws \Doctrine\ORM\ORMException if there is an issue with persisting the entity
     * @throws \Doctrine\DBAL\Exception   if there is a database error during the flush operation
     */
    public function createTask(TaskBlock $taskBlock, Request $request): Task
    {
        $task = new Task();
        $task->setTaskBlock($taskBlock);
        $this->populateTask($task, $request);

        $this->entityManager->persist($task);
        $this->entityManager->flush();

        return $task;
    }

    /**
     * Updates the given Task entity with data from the Request object.
     *
     * @param Task    $task    the task entity to update
     * @param Request $request the HTTP request containing the update data
     *
     * @return Task the updated task entity
     *
     * @throws \Doctrine\ORM\ORMException            if there is an issue persisting the entity
     * @throws \Doctrine\ORM\OptimisticLockException if a version check on the entity fails
     */
    public function updateTask(Task $task, Request $request): Task
    {
        $this->populateTask($task, $request);
        $this->entityManager->flush();

        return $task;
    }

    /**
     * Populates the given Task entity with data from the provided Request object.
     *
     * This method extracts data from the request and sets the corresponding fields
     * on the Task entity. It handles conditional logic for due dates, assignments,
     * and email templates. If certain fields are missing or invalid, default values
     * are applied.
     *
     * @param Task    $task    the Task entity to populate
     * @param Request $request the HTTP request containing the task data
     */
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
                if (false === $dateTime) {
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

        $actionType = $request->request->get('actionType', Task::ACTION_NONE);
        $task->setActionType($actionType);
        $task->setApiUrl($request->request->get('apiUrl'));

        if (Task::ACTION_EMAIL === $actionType) {
            $template = $request->request->get('emailTemplate');
            $uploadedFile = $request->files->get('emailTemplateFile');
            if ($uploadedFile && $uploadedFile->isValid()) {
                // Validate file size (e.g., max 2MB)
                $maxFileSize = 2 * 1024 * 1024; // 2MB
                if ($uploadedFile->getSize() > $maxFileSize) {
                    throw new \RuntimeException('Uploaded file exceeds the maximum allowed size of 2MB.');
                }

                // Validate MIME type (e.g., allow only text/plain or text/html)
                $allowedMimeTypes = ['text/plain', 'text/html'];
                if (!in_array($uploadedFile->getMimeType(), $allowedMimeTypes, true)) {
                    throw new \RuntimeException('Uploaded file type is not allowed.');
                }

                // Optionally scan for malicious content (e.g., using an external library or service)
                // Example: integrate a malware scanning library here if needed

                // Read file contents after validation
                $template = file_get_contents($uploadedFile->getPathname());
            }
            $task->setEmailTemplate($template);
        } else {
            $task->setEmailTemplate(null);
        }
    }
}
