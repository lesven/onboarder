<?php

namespace App\Tests\Controller;

use App\Controller\PublicTaskController;
use App\Entity\OnboardingTask;
use App\Repository\OnboardingTaskRepository;
use App\Service\TaskStatusService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

class PublicTaskControllerTest extends TestCase
{
    public function testCompleteMarksTask(): void
    {
        $task = new OnboardingTask();

        $repo = $this->createMock(OnboardingTaskRepository::class);
        $repo->method('findOneByCompletionToken')->willReturn($task);

        $service = $this->createMock(TaskStatusService::class);
        $service->expects($this->once())->method('markAsCompleted')->with($task);

        $controller = new class($service) extends PublicTaskController {
            public array $args;
            protected function render(string $view, array $parameters = [], ?Response $response = null): Response
            {
                $this->args = ['view' => $view];
                return new Response();
            }
        };

        $response = $controller->complete('tok', $repo);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame('public/task_complete.html.twig', $controller->args['view']);
    }
}
