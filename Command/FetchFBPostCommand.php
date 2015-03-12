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
        $facebookHelper = $container->get('facebook.helper');
        $facebookHelper->getPost();
    }
}
