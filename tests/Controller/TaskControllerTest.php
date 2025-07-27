<?php

namespace App\Tests\Controller;

use App\Controller\TaskController;
use App\Entity\Onboarding;
use App\Entity\OnboardingTask;
use App\Service\AdminLookupService;
use App\Service\OnboardingTaskFacade;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class TaskControllerTest extends TestCase
{
    public function testToggleCompleteRedirectsToOverview(): void
    {
        $task = new OnboardingTask();
        $task->setOnboarding(new Onboarding());

        $facade = $this->createMock(OnboardingTaskFacade::class);
        $facade->method('toggleStatus')->willReturn(['success', 'msg']);

        $lookup = $this->createMock(AdminLookupService::class);

        $controller = new class($facade, $lookup) extends TaskController {
            public string $route = '';
            protected function redirectToRoute(string $route, array $parameters = [], int $status = 302): \Symfony\Component\HttpFoundation\RedirectResponse
            {
                $this->route = $route;
                return new \Symfony\Component\HttpFoundation\RedirectResponse('/dummy', $status);
            }
            protected function addFlash(string $type, mixed $message): void
            {
                // no-op in tests
            }
        };

        $request = new Request([], [], [], [], [], ['HTTP_REFERER' => '/tasks']);
        $response = $controller->toggleComplete($task, $request);

        $this->assertSame('app_tasks_overview', $controller->route);
        $this->assertSame(302, $response->getStatusCode());
    }

    public function testDeleteRemovesTask(): void
    {
        $task = new OnboardingTask();
        $ob = new Onboarding();
        $task->setOnboarding($ob);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())->method('remove')->with($task);
        $em->expects($this->once())->method('flush');

        $facade = $this->createMock(OnboardingTaskFacade::class);
        $lookup = $this->createMock(AdminLookupService::class);

        $controller = new class($facade, $lookup) extends TaskController {
            public array $route;
            protected function redirectToRoute(string $route, array $parameters = [], int $status = 302): \Symfony\Component\HttpFoundation\RedirectResponse
            {
                $this->route = [$route, $parameters];
                return new \Symfony\Component\HttpFoundation\RedirectResponse('/dummy', $status);
            }
            protected function addFlash(string $type, mixed $message): void
            {
                // no-op
            }
        };

        $response = $controller->delete($task, $em);

        $this->assertSame('app_onboarding_detail', $controller->route[0]);
        $this->assertEquals(['id' => $ob->getId()], $controller->route[1]);
        $this->assertSame(302, $response->getStatusCode());
    }
}
