<?php

namespace SocialWallBundle\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use SocialWallBundle\Entity\SocialMediaConfig\InstagramConfig;

class LoadSocialMediaConfig implements FixtureInterface, OrderedFixtureInterface
{
    public function load(ObjectManager $manager)
    {
        $instagramTags = [
            'upro',
            'dudek',
            'lamernoire',
            'caribouAuSoleil',
        ];

        $manager->persist((new InstagramConfig())
            ->setTags($instagramTags)
        );
        $manager->flush();
    }

    public function getOrder()
    {
        return 1;
    }
}
