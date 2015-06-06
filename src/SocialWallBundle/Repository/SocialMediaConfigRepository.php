<?php

namespace SocialWallBundle\Repository;

use Doctrine\ORM\EntityRepository;
use SocialWallBundle\Entity\User;

class SocialMediaConfigRepository extends EntityRepository
{
    public function getConfigs(array $type, User $user, $singleResult = false)
    {
        $queryBuilder = $this->createQueryBuilder('s')
            ->andWhere('s INSTANCE OF :classes')
            ->andWhere('s.user = :user')
            ->setParameter('classes', $type)
            ->setParameter('user', $user)
        ;

        return $singleResult ? $queryBuilder->getQuery()->getSingleResult() : $queryBuilder->getQuery()->getResult();
    }
}
