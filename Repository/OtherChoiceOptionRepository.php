<?php

namespace SumoCoders\FrameworkCoreBundle\Repository;

use Doctrine\ORM\EntityRepository;
use SumoCoders\FrameworkCoreBundle\Entity\OtherChoiceOption;

final class OtherChoiceOptionRepository extends EntityRepository
{
    public function create(OtherChoiceOption $otherChoiceOption)
    {
        $this->getEntityManager()->persist($otherChoiceOption);
        $this->getEntityManager()->flush($otherChoiceOption);
    }
}
