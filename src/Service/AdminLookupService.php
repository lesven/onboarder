<?php

namespace App\Service;

use App\Entity\BaseType;
use App\Entity\OnboardingType;
use App\Entity\Role;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Provides helper methods to retrieve common admin reference data.
 */
class AdminLookupService
{
    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    /**
     * @return BaseType[]
     */
    public function getBaseTypes(): array
    {
        return $this->em->getRepository(BaseType::class)->findAll();
    }

    /**
     * @return OnboardingType[]
     */
    public function getOnboardingTypes(): array
    {
        return $this->em->getRepository(OnboardingType::class)->findAll();
    }

    /**
     * @return Role[]
     */
    public function getRoles(): array
    {
        return $this->em->getRepository(Role::class)->findAll();
    }
}
