<?php

namespace App\Tests\Controller\Admin;

use App\Controller\Admin\OnboardingAdminController;
use App\Entity\TaskBlock;
use App\Service\AdminLookupService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

class OnboardingAdminControllerTest extends TestCase
{
    public function testTaskBlocksRendersView(): void
    {
        $repo = $this->createMock(EntityRepository::class);
        $repo->method('findAll')->willReturn(['tb']);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getRepository')->willReturn($repo);

        $lookup = $this->createMock(AdminLookupService::class);

        $controller = new class($lookup) extends OnboardingAdminController {
            public array $args;
            protected function render(string $view, array $parameters = [], ?Response $response = null): Response
            {
                $this->args = ['view' => $view, 'params' => $parameters];
                return new Response();
            }
        };

        $response = $controller->taskBlocks($em);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame('admin/task_blocks.html.twig', $controller->args['view']);
        $this->assertSame(['taskBlocks' => ['tb']], $controller->args['params']);
    }
}
