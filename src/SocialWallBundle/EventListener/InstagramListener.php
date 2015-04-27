<?php

namespace SocialWallBundle\EventListener;

use Doctrine\Common\Persistence\ObjectManager;

use SocialWallBundle\Event\SocialMediaEvent;
use SocialWallBundle\Event\InstagramEvent;
use SocialWallBundle\Entity\SocialMediaPost\InstagramPost;
use SocialWallBundle\Services\InstagramHelper;
use Nc\FayeClient\Client;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\PostResponseEvent;

class InstagramListener implements EventSubscriberInterface
{
    private $tag = [];
    private $instagramHelper;
    private $om;
    private $token;
    private $lastMessage;
    private $client;

    public function __construct(InstagramHelper $instagramHelper, ObjectManager $om, Client $client)
    {
        $this->instagramHelper = $instagramHelper;
        $this->om = $om;
        $this->client = $client;
    }

    public static function getSubscribedEvents()
    {
        return [
            SocialMediaEvent::INSTAGRAM_NEW_DATA => 'onInstagramNewData',
            KernelEvents::TERMINATE => 'onKernelTerminate',
        ];
    }

    public function onInstagramNewData(InstagramEvent $event)
    {
        $this->tag[] = $event->getTag();
        $this->token = $event->getToken();
        $this->lastMessage = $event->getLastMessage();
    }

    public function onKernelTerminate(PostResponseEvent $event)
    {
        if (empty($this->tag)) {
            return;
        }
        $newMessages = [];
        foreach ($this->tag as $v) {
            foreach ($this->instagramHelper->searchForRecentTag($v, $this->token, $this->lastMessage) as $newPost) {
                $newMessages[] = $newPost;
                $this->om->persist((new InstagramPost())
                    ->setMessage($newPost['message'])
                    ->setCreated($newPost['created'])
                    ->setMinTagId($newPost['minTagId'])
                    ->setAuthorUsername($newPost['author'])
                    ->setTag($v)
                );
            }
        }
        $this->client->send('/social-feed', $newMessages);
        $this->tag = [];
        $this->om->flush();
    }
}
