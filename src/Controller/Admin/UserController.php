<?php

namespace App\Controller\Admin;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Verwaltung der Systembenutzer.
 */
#[Route('/admin/users')]
class UserController extends AbstractController
{
    #[Route('', name: 'app_admin_users')]
    public function list(EntityManagerInterface $entityManager): Response
    {
        $users = $entityManager->getRepository(User::class)->findAll();

        return $this->render('admin/users.html.twig', [
            'users' => $users,
        ]);
    }

    #[Route('/new', name: 'app_admin_user_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $hasher): Response
    {
        $user = new User();

        if ($request->isMethod('POST')) {
            $email = (string) $request->request->get('email');
            $password = (string) $request->request->get('password');

            if (strlen($password) < 8) {
                $this->addFlash('error', 'Passwort muss mindestens 8 Zeichen haben.');
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->addFlash('error', 'Ungültige E-Mail-Adresse.');
            } else {
                $user->setUsername($email);
                $user->setEmail($email);
                $user->setRoles(['ROLE_USER']);
                $user->setPassword($hasher->hashPassword($user, $password));

                $entityManager->persist($user);
                $entityManager->flush();

                $this->addFlash('success', 'Benutzer wurde erstellt.');

                return $this->redirectToRoute('app_admin_users');
            }
        }

        return $this->render('admin/user_form.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/{id}', name: 'app_admin_user_edit', requirements: ['id' => '\\d+'], methods: ['GET', 'POST'])]
    public function edit(User $user, Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $hasher): Response
    {
        if ($request->isMethod('POST')) {
            $email = (string) $request->request->get('email');
            $password = (string) $request->request->get('password');

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->addFlash('error', 'Ungültige E-Mail-Adresse.');
            } elseif ($password !== '' && strlen($password) < 8) {
                $this->addFlash('error', 'Passwort muss mindestens 8 Zeichen haben.');
            } else {
                $user->setUsername($email);
                $user->setEmail($email);
                if ($password !== '') {
                    $user->setPassword($hasher->hashPassword($user, $password));
                }

                $entityManager->flush();

                $this->addFlash('success', 'Benutzer wurde aktualisiert.');

                return $this->redirectToRoute('app_admin_users');
            }
        }

        return $this->render('admin/user_form.html.twig', [
            'user' => $user,
        ]);
    }
}
