<?php

namespace App\Controller\Admin;

use App\Entity\EmailSettings;
use App\Service\PasswordEncryptionService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/email-settings')]
class EmailSettingsController extends AbstractController
{
    #[Route('', name: 'app_admin_email_settings', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        EntityManagerInterface $entityManager,
        PasswordEncryptionService $encryptionService
    ): Response {
        $settings = $entityManager->getRepository(EmailSettings::class)->findOneBy([]) ?? new EmailSettings();
        
        // Setze den Verschlüsselungsservice in die Entity
        $settings->setEncryptionService($encryptionService);

        if ($request->isMethod('POST')) {
            $settings->setSmtpHost($request->request->get('smtpHost'));
            $settings->setSmtpPort((int) $request->request->get('smtpPort'));
            $settings->setSmtpUsername($request->request->get('smtpUsername') ?: null);
            
            // Nur setzen wenn ein neues Passwort eingegeben wurde
            $newPassword = $request->request->get('smtpPassword');
            if (!empty($newPassword)) {
                $settings->setSmtpPassword($newPassword);
            }
            
            $settings->setIgnoreSslCertificate($request->request->getBoolean('ignoreSsl'));
            $settings->setUpdatedAt(new \DateTimeImmutable());

            $entityManager->persist($settings);
            $entityManager->flush();

            $this->addFlash('success', 'E-Mail-Einstellungen gespeichert.');
        }

        return $this->render('admin/email_settings.html.twig', [
            'settings' => $settings,
        ]);
    }
}
