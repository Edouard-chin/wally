<?php

namespace SocialWallBundle\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;

class LoadUserData extends AbstractFixture implements ContainerAwareInterface, OrderedFixtureInterface
{
    private $container;

    /**
     * {@inheritDoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    public function load(ObjectManager $manager)
    {
        $userManager = $this->container->get('fos_user.user_manager');
        $config = $this->getReference('default-config');
        $user = $userManager->createUser()
            ->setPlainPassword('edouardpass')
            ->setUsername('echin')
            ->setEmail('chin.edouard@gmail.com')
            ->setRoles(['ROLE_ADMIN'])
            ->setEnabled(true)
            ->addSocialMediaConfig($config)
        ;
        $userManager->updateUser($user);
    }

    public function getOrder()
    {
        return 2;
    }
}
