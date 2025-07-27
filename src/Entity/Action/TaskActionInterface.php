<?php

namespace App\Entity\Action;

use App\Entity\OnboardingTask;

interface TaskActionInterface
{
    public function execute(OnboardingTask $task): void;
}
