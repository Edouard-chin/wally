<?php

namespace SocialWallBundle\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\DataFixtures\AbstractFixture;
use SocialWallBundle\Entity\SocialMediaConfig\InstagramConfig;

class LoadSocialMediaConfig extends AbstractFixture implements FixtureInterface, OrderedFixtureInterface
{
    public function load(ObjectManager $manager)
    {
        for ($i = 0; $i < 2; $i++) {
            $instagramConfig = new InstagramConfig();
            $manager->persist($instagramConfig);
            $this->setReference('default-config'.$i, $instagramConfig);
        }

        $manager->flush();
    }

    public function getOrder()
    {
        return 1;
    }
}
