<?php

namespace App\Tests\Controller;

use App\Controller\DashboardController;
use App\Entity\BaseType;
use App\Entity\Onboarding;
use App\Entity\OnboardingTask;
use App\Entity\OnboardingType;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

class DashboardControllerTest extends TestCase
{
    private function createQueryBuilderStub(mixed $result): \Doctrine\ORM\QueryBuilder
    {
        $query = $this->getMockBuilder(\Doctrine\ORM\Query::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getSingleScalarResult', 'getResult'])
            ->getMock();
        $query->method('getSingleScalarResult')->willReturn($result);
        $query->method('getResult')->willReturn($result);

        $qb = $this->getMockBuilder(\Doctrine\ORM\QueryBuilder::class)
            ->disableOriginalConstructor()
            ->onlyMethods([
                'select','leftJoin','addSelect','where','andWhere','setParameter','orderBy','setMaxResults','getQuery'
            ])
            ->getMock();

        foreach (['select','leftJoin','addSelect','where','andWhere','setParameter','orderBy','setMaxResults'] as $m) {
            $qb->method($m)->willReturn($qb);
        }

        $qb->method('getQuery')->willReturn($query);

        return $qb;
    }

    private function createRepositoryStub(array $queue, array $findBy = []): EntityRepository
    {
        $repo = $this->getMockBuilder(EntityRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['findBy', 'createQueryBuilder'])
            ->getMock();

        if (!empty($findBy)) {
            $repo->expects($this->once())
                ->method('findBy')
                ->willReturn($findBy);
        } else {
            $repo->method('findBy')->willReturn([]);
        }

        $builders = array_map(fn($res) => $this->createQueryBuilderStub($res), $queue);
        $repo->method('createQueryBuilder')
            ->willReturnOnConsecutiveCalls(...$builders);

        return $repo;
    }

    private function createEntityManager(array $map): EntityManagerInterface
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getRepository')
            ->willReturnCallback(fn(string $class) => $map[$class]);
        return $em;
    }

    public function testIndexRendersExpectedData(): void
    {
        $onboardings = ['ob1'];
        $onboardingRepo = $this->createRepositoryStub([
            5,
            3,
        ], $onboardings);

        $taskRepo = $this->createRepositoryStub([
            7,
            ['t1'],
            ['t2', 't3']
        ]);
        $typeRepo = $this->createRepositoryStub([2]);
        $baseRepo = $this->createRepositoryStub([4]);

        $em = $this->createEntityManager([
            Onboarding::class => $onboardingRepo,
            OnboardingTask::class => $taskRepo,
            OnboardingType::class => $typeRepo,
            BaseType::class => $baseRepo,
        ]);

        $controller = new class extends DashboardController {
            public array $renderArgs = [];
            protected function render(string $view, array $parameters = [], ?Response $response = null): Response
            {
                $this->renderArgs = ['view' => $view, 'params' => $parameters];
                return new Response('rendered');
            }
        };

        $response = $controller->index($em);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('dashboard/index.html.twig', $controller->renderArgs['view']);
        $this->assertEquals([
            'onboardings' => $onboardings,
            'stats' => [
                'total_onboardings' => 5,
                'active_onboardings' => 3,
                'open_tasks' => 7,
                'overdue_tasks' => 1,
                'total_types' => 2,
                'total_base_types' => 4,
            ],
            'overdueTasks' => ['t2', 't3'],
        ], $controller->renderArgs['params']);
    }

    public function testAdminRedirect(): void
    {
        $controller = new class extends DashboardController {
            public string $route = '';
            protected function redirectToRoute(string $route, array $parameters = [], int $status = 302): RedirectResponse
            {
                $this->route = $route;
                return new RedirectResponse('/dummy', $status);
            }
        };

        $response = $controller->adminRedirect();

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('app_admin_dashboard', $controller->route);
        $this->assertSame(302, $response->getStatusCode());
    }
}
