<?php

namespace App\Tests\Controller\Admin;

use App\Controller\Admin\EmailSettingsController;
use App\Entity\EmailSettings;
use App\Service\PasswordEncryptionService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class EmailSettingsControllerTest extends TestCase
{
    public function testIndexRendersSettings(): void
    {
        $settings = new EmailSettings();

        $repo = $this->createMock(EntityRepository::class);
        $repo->method('findOneBy')->willReturn($settings);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getRepository')->willReturn($repo);

        $em->expects($this->never())->method('persist');

        $service = new PasswordEncryptionService(new ParameterBag(['kernel.secret' => 's']));

        $controller = new class extends EmailSettingsController {
            public array $args;
            protected function render(string $view, array $parameters = [], ?Response $response = null): Response
            {
                $this->args = ['view' => $view, 'params' => $parameters];
                return new Response();
            }
        };

        $response = $controller->index(new Request(), $em, $service);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame('admin/email_settings.html.twig', $controller->args['view']);
        $this->assertSame(['settings' => $settings], $controller->args['params']);
    }
}
