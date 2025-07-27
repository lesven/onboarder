<?php

namespace App\Tests\Controller\Admin;

use App\Controller\Admin\UserController;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
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

        // Erstelle POST Request mit korrekten Parametern (email und password als separate Felder)
        $request = new Request([], ['email' => 'test@example.com', 'password' => 'short'], [], [], [], ['REQUEST_METHOD' => 'POST']);
        $controller->new($request, $em, $hasher);

        // Prüfe dass ein error Flash-Message hinzugefügt wurde (Passwort zu kurz)
        $this->assertArrayHasKey('error', $controller->flashes);
        $this->assertCount(1, $controller->flashes['error']);
        $this->assertStringContainsString('Passwort muss mindestens 8 Zeichen haben', $controller->flashes['error'][0]);
    }

    public function testNewInvalidEmail(): void
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

        // Erstelle POST Request mit ungültiger E-Mail
        $request = new Request([], ['email' => 'invalid-email', 'password' => 'validpassword123'], [], [], [], ['REQUEST_METHOD' => 'POST']);
        $controller->new($request, $em, $hasher);

        // Prüfe dass ein error Flash-Message hinzugefügt wurde (ungültige E-Mail)
        $this->assertArrayHasKey('error', $controller->flashes);
        $this->assertCount(1, $controller->flashes['error']);
        $this->assertStringContainsString('Ungültige E-Mail-Adresse', $controller->flashes['error'][0]);
    }

    public function testNewValidUser(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $hasher = $this->createMock(UserPasswordHasherInterface::class);
        
        // Mock den Hasher um ein gehashtes Passwort zu simulieren
        $hasher->method('hashPassword')->willReturn('hashed_password_123');
        
        // Erwarte dass persist und flush aufgerufen werden
        $em->expects($this->once())->method('persist');
        $em->expects($this->once())->method('flush');

        $controller = new class extends UserController {
            public array $args;
            public array $flashes = [];
            public bool $redirectCalled = false;
            protected function render(string $view, array $parameters = [], ?Response $response = null): Response
            {
                $this->args = ['view' => $view, 'params' => $parameters];
                return new Response();
            }
            protected function addFlash(string $type, mixed $message): void
            {
                $this->flashes[$type][] = $message;
            }
            protected function redirectToRoute(string $route, array $parameters = [], int $status = 302): RedirectResponse
            {
                $this->redirectCalled = true;
                return new RedirectResponse('http://example.com/redirect', $status);
            }
        };

        // Erstelle POST Request mit gültigen Daten
        $request = new Request([], ['email' => 'valid@example.com', 'password' => 'validpassword123'], [], [], [], ['REQUEST_METHOD' => 'POST']);
        $response = $controller->new($request, $em, $hasher);

        // Prüfe dass ein success Flash-Message hinzugefügt wurde
        $this->assertArrayHasKey('success', $controller->flashes);
        $this->assertCount(1, $controller->flashes['success']);
        $this->assertStringContainsString('Benutzer wurde erstellt', $controller->flashes['success'][0]);
        
        // Prüfe dass eine Weiterleitung erfolgte
        $this->assertTrue($controller->redirectCalled);
        $this->assertEquals(302, $response->getStatusCode());
    }
}
