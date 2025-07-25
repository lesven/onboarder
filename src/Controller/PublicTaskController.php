<?php

namespace App\Controller;

use App\Entity\OnboardingTask;
use App\Repository\OnboardingTaskRepository;
use App\Service\TaskStatusService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PublicTaskController extends AbstractController
{
    public function __construct(private readonly TaskStatusService $taskStatus)
    {
    }

    #[Route('/complete-task/{token}', name: 'app_public_task_complete')]
    public function complete(string $token, OnboardingTaskRepository $repository): Response
    {
        $task = $repository->findOneByCompletionToken($token);
        if (!$task) {
            return new Response('Ungültiger Link.', Response::HTTP_NOT_FOUND);
        }

        if (OnboardingTask::STATUS_COMPLETED !== $task->getStatus()) {
            $this->taskStatus->markAsCompleted($task);
        }

        return $this->render('public/task_complete.html.twig');
    }
}
