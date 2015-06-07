<?php

namespace SocialWallBundle\Repository;

use Doctrine\ORM\EntityRepository;

class InstagramConfigRepository extends EntityRepository
{
    public function retrieveConfigBySubscription($subscription)
    {
        $queryBuilder = $this->createQueryBuilder('i')
            ->andWhere('i.subscriptions LIKE :subscription')
            ->setParameter('subscription', '%'.$subscription.'%')
        ;

        return $queryBuilder->getQuery()->getSingleResult();
    }
}
