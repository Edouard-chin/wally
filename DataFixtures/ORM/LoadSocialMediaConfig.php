<?php

namespace SocialWallBundle\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;

use SocialWallBundle\Entity\SocialMediaConfig;
use SocialWallBundle\SocialMediaType;

class LoadSocialMediaConfig implements FixtureInterface, OrderedFixtureInterface
{
    public function load(ObjectManager $manager)
    {
        $instagramTags = [
            'upro',
            'dudek',
            'lamernoire',
            'caribouAuSoleil'
        ];

        $manager->persist((new SocialMediaConfig())
            ->setTags($instagramTags)
            ->setType(SocialMediaType::INSTAGRAM)
        );
        $manager->flush();
    }

    public function getOrder()
    {
        return 1;
    }

}
