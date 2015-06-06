<?php

namespace SocialWallBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Facebook\GraphPage;
use SocialWallBundle\Entity\SocialMediaConfig\FacebookConfig;

class FacebookConfigRepository extends EntityRepository
{
    public function updateOrCreatePage(GraphPage $page)
    {
        $storedPage = $this->findOneBy(['pageName' => $name = $page->getName()]);
        if (!$storedPage) {
            $storedPage = (new FacebookConfig())
                ->setToken($page->getProperty('access_token'))
                ->setPageName($name)
                ->setPageId($page->getId())
            ;
            $this->_em->persist($storedPage);

        } else {
            $storedPage->setToken($page->getProperty('access_token'));
        }

        return $storedPage;
    }
}
