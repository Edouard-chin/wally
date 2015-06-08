<?php

namespace SocialWallBundle\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use SocialWallBundle\Entity\SocialMediaConfig\InstagramConfig;

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
        $datas = [
            [
                'password' => 'edouardpass',
                'username' => 'echin',
                'email' => 'chin.edouard@gmail.com',
                'roles' => ['ROLE_ADMIN'],
            ],
            [
                'password' => 'shopifypass',
                'username' => 'shopify',
                'email' => 'user@shopify.com',
                'roles' => ['ROLE_ADMIN'],
            ],
        ];
        $userManager = $this->container->get('fos_user.user_manager');
        foreach ($datas as $key => $data) {
            $user = $userManager->createUser()
                ->setPlainPassword($data['password'])
                ->setUsername($data['username'])
                ->setEmail($data['email'])
                ->setRoles($data['roles'])
                ->setEnabled(true)
                ->addSocialMediaConfig($this->getReference('default-config'.$key))
            ;
            $userManager->updateUser($user);
        }
    }

    public function getOrder()
    {
        return 2;
    }
}
