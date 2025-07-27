<?php

namespace App\Tests\Controller\Admin;

use App\Controller\Admin\UserController;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\HttpFoundation\Request;

class UserControllerTest extends TestCase
{
    public function testListRendersUsers(): void
    {
        $repo = $this->createMock(EntityRepository::class);
        $repo->method('findAll')->willReturn(['u']);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getRepository')->willReturn($repo);

        $controller = new class extends UserController {
            public array $args;
            protected function render(string $view, array $parameters = [], ?Response $response = null): Response
            {
                $this->args = ['view' => $view, 'params' => $parameters];
                return new Response();
            }
        };

        $response = $controller->list($em);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame('admin/users.html.twig', $controller->args['view']);
        $this->assertSame(['users' => ['u']], $controller->args['params']);
    }

    public function testNewInvalidPassword(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $hasher = $this->createMock(UserPasswordHasherInterface::class);

        $controller = new class extends UserController {
            public array $args;
            public array $flashes = [];
            protected function render(string $view, array $parameters = [], ?Response $response = null): Response
            {
                $this->args = ['view' => $view, 'params' => $parameters];
                return new Response();
            }
            protected function addFlash(string $type, mixed $message): void
            {
                $this->flashes[$type][] = $message;
            }
        };

        $request = new Request([], ['email' => 'a@b.c', 'password' => 'short']);
        $controller->new($request, $em, $hasher);

        $this->assertArrayHasKey('error', $controller->flashes);
    }
}
