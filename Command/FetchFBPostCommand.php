<?php

namespace SocialWallBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use SocialWallBundle\Entity\SocialMediaPost\FacebookPost;


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
        $facebookHelper = $container->get('facebook.helper');
        $datas = $facebookHelper->getPost();
        foreach ($datas as $v) {
            $em->persist((new FacebookPost())
                ->setMessage($v['message'])
                ->setCreated($v['created'])
            );
        }
        $em->flush();
    }
}
