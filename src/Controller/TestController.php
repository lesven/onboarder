<?php

namespace App\Controller;

use App\Entity\Onboarding;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class TestController extends AbstractController
{
    #[Route('/test', name: 'app_test')]
    public function index(EntityManagerInterface $entityManager): Response
    {
        try {
            // Teste das Repository direkt
            $repository = $entityManager->getRepository(Onboarding::class);
            $count = $repository->count([]);
            
            return new Response("Repository funktioniert! Anzahl Onboardings: " . $count);
        } catch (\Exception $e) {
            return new Response("Fehler: " . $e->getMessage());
        }
    }
}
