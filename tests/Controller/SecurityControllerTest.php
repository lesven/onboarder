<?php

namespace App\Tests\Controller;

use App\Controller\SecurityController;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityControllerTest extends TestCase
{
    public function testLoginRendersTemplate(): void
    {
        $auth = $this->createMock(AuthenticationUtils::class);
        $auth->method('getLastAuthenticationError')->willReturn(null);
        $auth->method('getLastUsername')->willReturn('user');

        $controller = new class extends SecurityController {
            public array $args;
            protected function render(string $view, array $parameters = [], ?Response $response = null): Response
            {
                $this->args = ['view' => $view, 'params' => $parameters];
                return new Response();
            }
        };

        $response = $controller->login($auth);

        $this->assertSame('security/login.html.twig', $controller->args['view']);
        $this->assertSame(['last_username' => 'user', 'error' => null], $controller->args['params']);
        $this->assertInstanceOf(Response::class, $response);
    }
}
