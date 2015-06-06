<?php

namespace SocialWallBundle\Repository;

use Doctrine\ORM\EntityRepository;
use SocialWallBundle\Entity\User;

class SocialMediaConfigRepository extends EntityRepository
{
    public function getConfigs(array $type, User $user)
    {
        $queryBuilder = $this->createQueryBuilder('s')
            ->andWhere('s INSTANCE OF :classes')
            ->andWhere('s.user = :user')
            ->setParameter('classes', $type)
            ->setParameter('user', $user)
        ;

        return $queryBuilder->getQuery()->getResult();
    }
}
