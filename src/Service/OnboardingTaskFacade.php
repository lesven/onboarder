<?php

namespace App\Service;

use App\Entity\Onboarding;
use App\Entity\OnboardingTask;
use Symfony\Component\HttpFoundation\Request;

/**
 * Facade-Service für OnboardingTask-Operationen.
 * 
 * Koordiniert spezialisierte Services und stellt eine einheitliche API bereit.
 */
class OnboardingTaskFacade
{
    public function __construct(
        private readonly TaskGenerationService $taskGeneration,
        private readonly DueDateManagementService $dueDateManagement,
        private readonly TaskStatusService $taskStatus,
        private readonly TaskQueryService $taskQuery,
        private readonly TaskRequestMappingService $requestMapping
    ) {
    }

    /**
     * Erzeugt OnboardingTasks basierend auf dem zugewiesenen OnboardingType.
     */
    public function generateForOnboarding(Onboarding $onboarding): void
    {
        $this->taskGeneration->generateForOnboarding($onboarding);
    }

    /**
     * Aktualisiert alle relativen Fälligkeiten nach Anpassung des Eintrittsdatums.
     */
    public function updateDueDates(Onboarding $onboarding): void
    {
        $this->dueDateManagement->updateDueDates($onboarding);
    }

    /**
     * Schaltet den Status einer Aufgabe um.
     *
     * @return array{string,string} Typ und Meldung für Flash-Nachricht
     */
    public function toggleStatus(OnboardingTask $task): array
    {
        return $this->taskStatus->toggleStatus($task);
    }

    /**
     * Liefert gefilterte Aufgaben für die Übersicht.
     *
     * @return OnboardingTask[]
     */
    public function getFilteredTasks(string $status = '', string $employee = '', string $assignee = ''): array
    {
        return $this->taskQuery->getFilteredTasks($status, $employee, $assignee);
    }

    /**
     * Erstellt eine neue OnboardingTask anhand der Request-Daten.
     */
    public function createOnboardingTask(Onboarding $onboarding, Request $request): OnboardingTask
    {
        return $this->requestMapping->createOnboardingTask($onboarding, $request);
    }

    /**
     * Aktualisiert eine bestehende OnboardingTask.
     */
    public function updateOnboardingTask(OnboardingTask $task, Request $request): OnboardingTask
    {
        return $this->requestMapping->updateOnboardingTask($task, $request);
    }
}
