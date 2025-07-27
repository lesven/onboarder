<?php

namespace App\Tests\Controller\Admin;

use App\Controller\Admin\SettingsController;
use App\Entity\BaseType;
use App\Entity\OnboardingType;
use App\Entity\Role;
use App\Entity\TaskBlock;
use App\Entity\User;
use App\Service\AdminLookupService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

class SettingsControllerTest extends TestCase
{
    public function testIndexRendersStats(): void
    {
        $repo = $this->createMock(EntityRepository::class);
        $repo->method('count')->willReturn(1);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getRepository')->willReturn($repo);

        $lookup = $this->createMock(AdminLookupService::class);

        $controller = new class($lookup) extends SettingsController {
            public array $args;
            protected function render(string $view, array $parameters = [], ?Response $response = null): Response
            {
                $this->args = ['view' => $view, 'params' => $parameters];
                return new Response();
            }
        };

        $response = $controller->index($em);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame('admin/index.html.twig', $controller->args['view']);
        $this->assertSame(['stats' => [
            'baseTypes' => 1,
            'onboardingTypes' => 1,
            'roles' => 1,
            'taskBlocks' => 1,
            'users' => 1,
        ]], $controller->args['params']);
    }
}
