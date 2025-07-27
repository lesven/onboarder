<?php

namespace App\Tests\Controller;

use App\Controller\OnboardingController;
use App\Entity\Onboarding;
use App\Entity\OnboardingTask;
use App\Entity\TaskBlock;
use App\Service\OnboardingTaskFacade;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

class OnboardingControllerTest extends TestCase
{
    public function testIndexRendersList(): void
    {
        $repo = $this->createMock(EntityRepository::class);
        $repo->expects($this->once())
            ->method('findBy')
            ->willReturn(['ob']);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getRepository')->willReturn($repo);

        $facade = $this->createMock(OnboardingTaskFacade::class);

        $controller = new class($facade) extends OnboardingController {
            public array $args;
            protected function render(string $view, array $parameters = [], ?Response $response = null): Response
            {
                $this->args = ['view' => $view, 'params' => $parameters];
                return new Response();
            }
        };

        $response = $controller->index($em);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame('dashboard/onboardings.html.twig', $controller->args['view']);
        $this->assertSame(['onboardings' => ['ob']], $controller->args['params']);
    }

    public function testGroupTasksByBlock(): void
    {
        $onboarding = new Onboarding();

        $block = new TaskBlock();
        $block->setName('Block');

        $task1 = new OnboardingTask();
        $task1->setTaskBlock($block);
        $task2 = new OnboardingTask();
        $task2->setTaskBlock(null);

        $onboarding->getOnboardingTasks()->add($task1);
        $onboarding->getOnboardingTasks()->add($task2);

        $facade = $this->createMock(OnboardingTaskFacade::class);
        $controller = new OnboardingController($facade);

        $ref = new \ReflectionClass($controller);
        $method = $ref->getMethod('groupTasksByBlock');
        $method->setAccessible(true);

        $grouped = $method->invoke($controller, $onboarding);

        $this->assertCount(2, $grouped);
        $this->assertArrayHasKey('Block', $grouped);
        $this->assertArrayHasKey('Sonderaufgaben', $grouped);
    }
}
