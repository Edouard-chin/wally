<?php

namespace SocialWallBundle\Entity;

use Doctrine\ORM\EntityRepository;

class SocialMediaPostRepository extends EntityRepository
{
    /**
     * @param array $type    An array with namespace classes or DiscriminatorMap keys
     */
    public function getPost(array $type = null)
    {
        $queryBuilder = $this->createQueryBuilder('s');

        if ($type) {
            $queryBuilder
                ->andWhere('s INSTANCE OF :classes')
                ->setParameter('classes', $type)
            ;
        }

        return $queryBuilder->getQuery()->getResult();
    }
}
