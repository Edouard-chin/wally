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
        $instagramConfig = new InstagramConfig();


        $manager->persist($instagramConfig);
        $manager->flush();
        $this->setReference('default-config', $instagramConfig);
    }

    public function getOrder()
    {
        return 1;
    }
}
