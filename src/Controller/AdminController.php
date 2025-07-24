<?php

namespace App\Controller;

use App\Entity\BaseType;
use App\Entity\OnboardingType;
use App\Entity\Role;
use App\Entity\TaskBlock;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin')]
class AdminController extends AbstractController
{
    #[Route('/', name: 'app_admin_dashboard')]
    public function index(EntityManagerInterface $entityManager): Response
    {
        $stats = [
            'baseTypes' => $entityManager->getRepository(BaseType::class)->count([]),
            'onboardingTypes' => $entityManager->getRepository(OnboardingType::class)->count([]),
            'roles' => $entityManager->getRepository(Role::class)->count([]),
            'taskBlocks' => $entityManager->getRepository(TaskBlock::class)->count([]),
        ];

        return $this->render('admin/index.html.twig', [
            'stats' => $stats,
        ]);
    }

    #[Route('/base-types', name: 'app_admin_base_types')]
    public function baseTypes(EntityManagerInterface $entityManager): Response
    {
        $baseTypes = $entityManager->getRepository(BaseType::class)->findAll();

        return $this->render('admin/base_types.html.twig', [
            'baseTypes' => $baseTypes,
        ]);
    }

    #[Route('/onboarding-types', name: 'app_admin_onboarding_types')]
    public function onboardingTypes(EntityManagerInterface $entityManager): Response
    {
        $onboardingTypes = $entityManager->getRepository(OnboardingType::class)->findAll();

        return $this->render('admin/onboarding_types.html.twig', [
            'onboardingTypes' => $onboardingTypes,
        ]);
    }

    #[Route('/roles', name: 'app_admin_roles')]
    public function roles(EntityManagerInterface $entityManager): Response
    {
        $roles = $entityManager->getRepository(Role::class)->findAll();

        return $this->render('admin/roles.html.twig', [
            'roles' => $roles,
        ]);
    }

    #[Route('/task-blocks', name: 'app_admin_task_blocks')]
    public function taskBlocks(EntityManagerInterface $entityManager): Response
    {
        $taskBlocks = $entityManager->getRepository(TaskBlock::class)->findAll();

        return $this->render('admin/task_blocks.html.twig', [
            'taskBlocks' => $taskBlocks,
        ]);
    }

    #[Route('/base-type/new', name: 'app_admin_base_type_new')]
    public function newBaseType(Request $request, EntityManagerInterface $entityManager): Response
    {
        if ($request->isMethod('POST')) {
            $baseType = new BaseType();
            $baseType->setName($request->request->get('name'));
            $baseType->setDescription($request->request->get('description'));

            $entityManager->persist($baseType);
            $entityManager->flush();

            $this->addFlash('success', 'BaseType wurde erfolgreich erstellt!');
            return $this->redirectToRoute('app_admin_base_types');
        }

        return $this->render('admin/base_type_form.html.twig');
    }

    #[Route('/role/new', name: 'app_admin_role_new')]
    public function newRole(Request $request, EntityManagerInterface $entityManager): Response
    {
        if ($request->isMethod('POST')) {
            $role = new Role();
            $role->setName($request->request->get('name'));
            $role->setEmail($request->request->get('email'));
            $role->setDescription($request->request->get('description'));

            $entityManager->persist($role);
            $entityManager->flush();

            $this->addFlash('success', 'Rolle wurde erfolgreich erstellt!');
            return $this->redirectToRoute('app_admin_roles');
        }

        return $this->render('admin/role_form.html.twig');
    }
}
