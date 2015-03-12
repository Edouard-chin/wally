<?php

namespace SocialWallBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


class FetchFBPostCommand extends ContainerAwareCommand
{
    /**
     * @see Command
     */
    protected function configure()
    {
        $this
            ->setName('participation:facebook:fetch')
            ->setDescription('Fetch facebook for events')
            ->setHelp(<<<EOT
The <info>participation:facebook:fetch</info> command fetches new facebook post and persist them in db
EOT
            );
    }

    /**
     * @see Command
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $container = $this->getContainer();
        $em = $container->get('doctrine.orm.entity_manager');
        $accessToken = $em->getRepository('SocialWallBundle:AccessToken')->find(2);
        if (!$accessToken) {
            throw new \Exception('No token Found');
        }
        $token = $accessToken->getToken();
    }
}
