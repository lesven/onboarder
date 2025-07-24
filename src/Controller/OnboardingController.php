<?php

namespace App\Controller;

use App\Entity\Onboarding;
use App\Entity\OnboardingType;
use App\Service\OnboardingTaskFacade;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Controller für Onboarding CRUD-Operationen.
 */
#[Route('/onboarding')]
class OnboardingController extends AbstractController
{
    public function __construct(private readonly OnboardingTaskFacade $taskService)
    {
    }

    #[Route('/new', name: 'app_onboarding_new')]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        if ($request->isMethod('POST')) {
            $onboarding = $this->createOnboardingFromRequest($request, $entityManager);
            
            // Tasks aus TaskBlocks generieren
            $this->taskService->generateForOnboarding($onboarding);

            $this->addFlash('success', 'Onboarding für '.$onboarding->getFullName().' wurde erfolgreich erstellt!');

            return $this->redirectToRoute('app_onboardings');
        }

        // OnboardingTypes für das Formular laden
        $onboardingTypes = $entityManager->getRepository(OnboardingType::class)->findAll();

        return $this->render('dashboard/onboarding_form.html.twig', [
            'onboardingTypes' => $onboardingTypes,
        ]);
    }

    #[Route('s', name: 'app_onboardings')]
    public function index(EntityManagerInterface $entityManager): Response
    {
        // Alle Onboardings laden
        $onboardings = $entityManager->getRepository(Onboarding::class)
            ->findBy([], ['createdAt' => 'DESC']);

        return $this->render('dashboard/onboardings.html.twig', [
            'onboardings' => $onboardings,
        ]);
    }

    #[Route('/{id}', name: 'app_onboarding_detail', requirements: ['id' => '\d+'])]
    public function detail(Onboarding $onboarding): Response
    {
        // OnboardingTasks nach TaskBlocks gruppieren
        $tasksByBlock = $this->groupTasksByBlock($onboarding);

        return $this->render('dashboard/onboarding_detail.html.twig', [
            'onboarding' => $onboarding,
            'tasks' => $onboarding->getOnboardingTasks(), // Für Statistiken
            'tasksByBlock' => $tasksByBlock,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_onboarding_delete', methods: ['POST'])]
    public function delete(Onboarding $onboarding, EntityManagerInterface $entityManager): Response
    {
        $fullName = $onboarding->getFullName();

        // Onboarding löschen (OnboardingTasks werden automatisch durch cascade gelöscht)
        $entityManager->remove($onboarding);
        $entityManager->flush();

        $this->addFlash('success', 'Onboarding für '.$fullName.' wurde erfolgreich gelöscht!');

        return $this->redirectToRoute('app_onboardings');
    }

    /**
     * Erstellt ein neues Onboarding aus Request-Daten.
     */
    private function createOnboardingFromRequest(Request $request, EntityManagerInterface $entityManager): Onboarding
    {
        $onboarding = new Onboarding();
        $onboarding->setFirstName($request->request->get('firstName'));
        $onboarding->setLastName($request->request->get('lastName'));
        $onboarding->setEntryDate(new \DateTimeImmutable($request->request->get('entryDate')));
        $onboarding->setPosition($request->request->get('position'));
        $onboarding->setTeam($request->request->get('team'));
        $onboarding->setManager($request->request->get('manager'));
        $onboarding->setManagerEmail($request->request->get('managerEmail'));
        $onboarding->setBuddy($request->request->get('buddy'));
        $onboarding->setBuddyEmail($request->request->get('buddyEmail'));

        // OnboardingType zuweisen falls ausgewählt
        $onboardingTypeId = $request->request->get('onboardingType');
        if ($onboardingTypeId) {
            $onboardingType = $entityManager->getRepository(OnboardingType::class)->find($onboardingTypeId);
            if ($onboardingType) {
                $onboarding->setOnboardingType($onboardingType);
            }
        }

        $entityManager->persist($onboarding);
        $entityManager->flush();

        return $onboarding;
    }

    /**
     * Gruppiert OnboardingTasks nach TaskBlocks für die Anzeige.
     */
    private function groupTasksByBlock(Onboarding $onboarding): array
    {
        $tasksByBlock = [];
        foreach ($onboarding->getOnboardingTasks() as $task) {
            $blockName = $task->getTaskBlock() ? $task->getTaskBlock()->getName() : 'Sonderaufgaben';
            if (!isset($tasksByBlock[$blockName])) {
                $tasksByBlock[$blockName] = [];
            }
            $tasksByBlock[$blockName][] = $task;
        }

        // Nach Block-Namen sortieren (alphabetisch)
        ksort($tasksByBlock);

        return $tasksByBlock;
    }
}
