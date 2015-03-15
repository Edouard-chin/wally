<?php

namespace SocialWallBundle\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;

use SocialWallBundle\Entity\InstagramConfig;

class LoadInstagramConfig implements FixtureInterface, OrderedFixtureInterface
{
    public function load(ObjectManager $manager)
    {
        $tags = [
            'upro',
            'dudek',
            'lamernoire',
            'caribouAuSoleil'
        ];

        $manager->persist((new InstagramConfig())->setTags($tags));
        $manager->flush();
    }

    public function getOrder()
    {
        return 1;
    }

}
