<?php

namespace App\Tests\Controller;

use App\Controller\TaskFormController;
use App\Entity\Onboarding;
use App\Entity\OnboardingTask;
use App\Entity\Role;
use App\Service\OnboardingTaskFacade;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpFoundation\Response;

class TaskFormControllerTest extends TestCase
{
    public function testAddThrowsWhenOnboardingMissing(): void
    {
        $repo = $this->createMock(EntityRepository::class);
        $repo->method('find')->willReturn(null);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getRepository')->willReturn($repo);

        $facade = $this->createMock(OnboardingTaskFacade::class);

        $controller = new TaskFormController($facade);

        $this->expectException(NotFoundHttpException::class);
        $controller->add(1, new Request(), $em);
    }

    public function testAddRendersForm(): void
    {
        $onboarding = new Onboarding();
        $repo = $this->createMock(EntityRepository::class);
        $repo->method('find')->willReturn($onboarding);

        $roleRepo = $this->createMock(EntityRepository::class);
        $roleRepo->method('findAll')->willReturn([]);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getRepository')->willReturnMap([
            [Onboarding::class, $repo],
            [Role::class, $roleRepo],
        ]);

        $facade = $this->createMock(OnboardingTaskFacade::class);

        $controller = new class($facade) extends TaskFormController {
            public array $args;
            protected function render(string $view, array $parameters = [], ?Response $response = null): Response
            {
                $this->args = ['view' => $view, 'params' => $parameters];
                return new Response();
            }
        };

        $response = $controller->add(1, new Request(), $em);

        $this->assertSame('dashboard/onboarding_task_form.html.twig', $controller->args['view']);
        $this->assertSame(['onboarding' => $onboarding, 'roles' => [], 'task' => null], $controller->args['params']);
        $this->assertInstanceOf(Response::class, $response);
    }
}
