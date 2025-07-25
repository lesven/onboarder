<?php

namespace App\Controller\Admin;

use App\Entity\BaseType;
use App\Entity\OnboardingType;
use App\Entity\Role;
use App\Entity\TaskBlock;
use App\Entity\User;
use App\Service\AdminLookupService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Handles the general admin settings like base types, onboarding types and roles.
 */
#[Route('/admin')]
class SettingsController extends AbstractController
{
    public function __construct(private readonly AdminLookupService $lookup)
    {
    }

    #[Route('/', name: 'app_admin_dashboard')]
    public function index(EntityManagerInterface $entityManager): Response
    {
        $stats = [
            'baseTypes' => $entityManager->getRepository(BaseType::class)->count([]),
            'onboardingTypes' => $entityManager->getRepository(OnboardingType::class)->count([]),
            'roles' => $entityManager->getRepository(Role::class)->count([]),
            'taskBlocks' => $entityManager->getRepository(TaskBlock::class)->count([]),
            'users' => $entityManager->getRepository(User::class)->count([]),
        ];

        return $this->render('admin/index.html.twig', [
            'stats' => $stats,
        ]);
    }

    #[Route('/base-types', name: 'app_admin_base_types')]
    public function baseTypes(): Response
    {
        return $this->render('admin/base_types.html.twig', [
            'baseTypes' => $this->lookup->getBaseTypes(),
        ]);
    }

    #[Route('/onboarding-types', name: 'app_admin_onboarding_types')]
    public function onboardingTypes(): Response
    {
        return $this->render('admin/onboarding_types.html.twig', [
            'onboardingTypes' => $this->lookup->getOnboardingTypes(),
        ]);
    }

    #[Route('/roles', name: 'app_admin_roles')]
    public function roles(): Response
    {
        return $this->render('admin/roles.html.twig', [
            'roles' => $this->lookup->getRoles(),
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

    #[Route('/base-type/{id}', name: 'app_admin_base_type_show')]
    public function showBaseType(BaseType $baseType): Response
    {
        return $this->render('admin/base_type_show.html.twig', [
            'baseType' => $baseType,
        ]);
    }

    #[Route('/base-type/{id}/edit', name: 'app_admin_base_type_edit')]
    public function editBaseType(Request $request, BaseType $baseType, EntityManagerInterface $entityManager): Response
    {
        if ($request->isMethod('POST')) {
            $name = $request->request->get('name');
            $description = $request->request->get('description');

            if (empty($name)) {
                $this->addFlash('error', 'Der Name darf nicht leer sein.');

                return $this->render('admin/base_type_form.html.twig', [
                    'baseType' => $baseType,
                ]);
            }

            $existingBaseType = $entityManager->getRepository(BaseType::class)->findOneBy(['name' => $name]);
            if ($existingBaseType && $existingBaseType->getId() !== $baseType->getId()) {
                $this->addFlash('error', 'Der Name muss eindeutig sein.');

                return $this->render('admin/base_type_form.html.twig', [
                    'baseType' => $baseType,
                ]);
            }

            $baseType->setName($name);
            $baseType->setDescription($description);
            $baseType->setUpdatedAt(new \DateTimeImmutable());

            $entityManager->flush();

            $this->addFlash('success', 'BaseType wurde erfolgreich aktualisiert!');

            return $this->redirectToRoute('app_admin_base_types');
        }

        return $this->render('admin/base_type_form.html.twig', [
            'baseType' => $baseType,
        ]);
    }

    #[Route('/base-type/{id}/delete', name: 'app_admin_base_type_delete')]
    public function deleteBaseType(BaseType $baseType, EntityManagerInterface $entityManager): Response
    {
        $entityManager->remove($baseType);
        $entityManager->flush();

        $this->addFlash('success', 'BaseType wurde erfolgreich gelöscht!');

        return $this->redirectToRoute('app_admin_base_types');
    }

    #[Route('/role/new', name: 'app_admin_role_new')]
    public function newRole(Request $request, EntityManagerInterface $entityManager): Response
    {
        if ($request->isMethod('POST')) {
            // Eingabedaten extrahieren und trimmen
            $name = trim($request->request->get('name', ''));
            $email = trim($request->request->get('email', ''));
            $description = trim($request->request->get('description', ''));

            // Validierung der erforderlichen Felder
            if (empty($name)) {
                $this->addFlash('error', 'Der Name darf nicht leer sein.');

                return $this->render('admin/role_form.html.twig');
            }

            if (empty($email)) {
                $this->addFlash('error', 'Die E-Mail-Adresse darf nicht leer sein.');

                return $this->render('admin/role_form.html.twig');
            }

            // E-Mail-Format validieren
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->addFlash('error', 'Die E-Mail-Adresse ist ungültig.');

                return $this->render('admin/role_form.html.twig');
            }

            // Längenbeschränkungen prüfen
            if (strlen($name) > 255) {
                $this->addFlash('error', 'Der Name darf maximal 255 Zeichen lang sein.');

                return $this->render('admin/role_form.html.twig');
            }

            if (strlen($email) > 255) {
                $this->addFlash('error', 'Die E-Mail-Adresse darf maximal 255 Zeichen lang sein.');

                return $this->render('admin/role_form.html.twig');
            }

            // Eindeutigkeit des Namens prüfen
            $existingRole = $entityManager->getRepository(Role::class)->findOneBy(['name' => $name]);
            if ($existingRole) {
                $this->addFlash('error', 'Der Name muss eindeutig sein.');

                return $this->render('admin/role_form.html.twig');
            }

            $role = new Role();
            $role->setName($name);
            $role->setEmail($email);
            $role->setDescription($description);

            $entityManager->persist($role);
            $entityManager->flush();

            $this->addFlash('success', 'Rolle wurde erfolgreich erstellt!');

            return $this->redirectToRoute('app_admin_roles');
        }

        return $this->render('admin/role_form.html.twig');
    }

    #[Route('/role/{id}', name: 'app_admin_role_show')]
    public function showRole(Role $role): Response
    {
        return $this->render('admin/role_show.html.twig', [
            'role' => $role,
        ]);
    }

    #[Route('/role/{id}/edit', name: 'app_admin_role_edit')]
    public function editRole(Request $request, Role $role, EntityManagerInterface $entityManager): Response
    {
        if ($request->isMethod('POST')) {
            // Eingabedaten extrahieren und trimmen
            $name = trim($request->request->get('name', ''));
            $email = trim($request->request->get('email', ''));
            $description = trim($request->request->get('description', ''));

            // Validierung der erforderlichen Felder
            if (empty($name)) {
                $this->addFlash('error', 'Der Name darf nicht leer sein.');

                return $this->render('admin/role_form.html.twig', [
                    'role' => $role,
                ]);
            }

            if (empty($email)) {
                $this->addFlash('error', 'Die E-Mail-Adresse darf nicht leer sein.');

                return $this->render('admin/role_form.html.twig', [
                    'role' => $role,
                ]);
            }

            // E-Mail-Format validieren
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->addFlash('error', 'Die E-Mail-Adresse ist ungültig.');

                return $this->render('admin/role_form.html.twig', [
                    'role' => $role,
                ]);
            }

            // Längenbeschränkungen prüfen (entsprechend der Datenbankschema)
            if (strlen($name) > 255) {
                $this->addFlash('error', 'Der Name darf maximal 255 Zeichen lang sein.');

                return $this->render('admin/role_form.html.twig', [
                    'role' => $role,
                ]);
            }

            if (strlen($email) > 255) {
                $this->addFlash('error', 'Die E-Mail-Adresse darf maximal 255 Zeichen lang sein.');

                return $this->render('admin/role_form.html.twig', [
                    'role' => $role,
                ]);
            }

            // Eindeutigkeit des Namens prüfen
            $existingRole = $entityManager->getRepository(Role::class)->findOneBy(['name' => $name]);
            if ($existingRole && $existingRole->getId() !== $role->getId()) {
                $this->addFlash('error', 'Der Name muss eindeutig sein.');

                return $this->render('admin/role_form.html.twig', [
                    'role' => $role,
                ]);
            }

            // Sanitisierte Daten setzen
            $role->setName($name);
            $role->setEmail($email);
            $role->setDescription($description);
            $role->setUpdatedAt(new \DateTimeImmutable());

            $entityManager->flush();

            $this->addFlash('success', 'Rolle wurde erfolgreich aktualisiert!');

            return $this->redirectToRoute('app_admin_roles');
        }

        return $this->render('admin/role_form.html.twig', [
            'role' => $role,
        ]);
    }

    #[Route('/role/{id}/delete', name: 'app_admin_role_delete')]
    public function deleteRole(Request $request, Role $role, EntityManagerInterface $entityManager): Response
    {
        $csrfToken = $request->request->get('_csrf_token');
        
        $entityManager->remove($role);
        $entityManager->flush();

        $this->addFlash('success', 'Rolle wurde erfolgreich gelöscht!');

        return $this->redirectToRoute('app_admin_roles');
    }

    #[Route('/onboarding-type/new', name: 'app_admin_onboarding_type_new')]
    public function newOnboardingType(Request $request, EntityManagerInterface $entityManager): Response
    {
        if ($request->isMethod('POST')) {
            $onboardingType = new OnboardingType();
            $onboardingType->setName($request->request->get('name'));
            $onboardingType->setDescription($request->request->get('description'));

            $baseTypeId = $request->request->get('baseType');
            if ($baseTypeId) {
                $baseType = $entityManager->getRepository(BaseType::class)->find($baseTypeId);
                if ($baseType) {
                    $onboardingType->setBaseType($baseType);
                }
            }

            $entityManager->persist($onboardingType);
            $entityManager->flush();

            $this->addFlash('success', 'OnboardingType wurde erfolgreich erstellt!');

            return $this->redirectToRoute('app_admin_onboarding_types');
        }

        return $this->render('admin/onboarding_type_form.html.twig', [
            'baseTypes' => $this->lookup->getBaseTypes(),
        ]);
    }

    #[Route('/onboarding-type/{id}', name: 'app_admin_onboarding_type_show')]
    public function showOnboardingType(OnboardingType $onboardingType): Response
    {
        return $this->render('admin/onboarding_type_show.html.twig', [
            'onboardingType' => $onboardingType,
        ]);
    }

    #[Route('/onboarding-type/{id}/edit', name: 'app_admin_onboarding_type_edit')]
    public function editOnboardingType(Request $request, OnboardingType $onboardingType, EntityManagerInterface $entityManager): Response
    {
        if ($request->isMethod('POST')) {
            $onboardingType->setName($request->request->get('name'));
            $onboardingType->setDescription($request->request->get('description'));

            $baseTypeId = $request->request->get('baseType');
            $baseType = null;
            if ($baseTypeId) {
                $baseType = $entityManager->getRepository(BaseType::class)->find($baseTypeId);
            }
            $onboardingType->setBaseType($baseType);
            $onboardingType->setUpdatedAt(new \DateTimeImmutable());

            $entityManager->flush();

            $this->addFlash('success', 'OnboardingType wurde erfolgreich aktualisiert!');

            return $this->redirectToRoute('app_admin_onboarding_types');
        }

        return $this->render('admin/onboarding_type_form.html.twig', [
            'onboardingType' => $onboardingType,
            'baseTypes' => $this->lookup->getBaseTypes(),
        ]);
    }

    #[Route('/onboarding-type/{id}/delete', name: 'app_admin_onboarding_type_delete')]
    public function deleteOnboardingType(OnboardingType $onboardingType, EntityManagerInterface $entityManager): Response
    {
        $entityManager->remove($onboardingType);
        $entityManager->flush();

        $this->addFlash('success', 'OnboardingType wurde erfolgreich gelöscht!');

        return $this->redirectToRoute('app_admin_onboarding_types');
    }
}
