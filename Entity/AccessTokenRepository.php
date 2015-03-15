<?php

namespace SocialWallBundle\Entity;

use Doctrine\ORM\EntityRepository;

use SocialWallBundle\Entity\AccessToken;

class AccessTokenRepository extends EntityRepository
{
    public function updateOrCreate($type, $accessToken)
    {
        $token = $this->findOneBy([
            'type' => $type
        ]);

        if (!$token) {
            $this->_em->persist((new AccessToken())
                ->setType($type)
                ->setToken($accessToken)
            );
        } else {
            $token->setToken($accessToken);
        }

        return $token;
    }
}
