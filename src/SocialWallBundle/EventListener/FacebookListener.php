<?php

namespace SocialWallBundle\EventListener;

use Doctrine\Common\Persistence\ObjectManager;
use SocialWallBundle\Event\SocialMediaEvent;
use SocialWallBundle\Event\FacebookEvent;
use SocialWallBundle\Entity\SocialMediaPost\FacebookPost;
use SocialWallBundle\Services\FacebookHelper;
use Nc\FayeClient\Client;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\PostResponseEvent;

class FacebookListener implements EventSubscriberInterface
{
    private $content = null;
    private $facebookHelper;
    private $om;
    private $token;
    private $type;
    private $client;

    public function __construct(FacebookHelper $facebookHelper, ObjectManager $om, Client $client)
    {
        $this->facebookHelper = $facebookHelper;
        $this->om = $om;
        $this->client = $client;
    }

    public static function getSubscribedEvents()
    {
        return [
            SocialMediaEvent::FACEBOOK_NEW_DATA => 'onFacebookNewData',
            KernelEvents::TERMINATE => ['onKernelTerminate', -1025],
        ];
    }

    public function onFacebookNewData(FacebookEvent $event)
    {
        $this->token = $event->getToken();
        $this->content = $event->getContent();
        $this->type = $event->getType();
    }

    public function onKernelTerminate(PostResponseEvent $event)
    {
        if (!$this->content && $this->type == 'fetch') {
            return;
        }
        switch ($this->type) {
            case 'import':
                $newMessages = $this->facebookHelper->getOlderPosts($this->token);
                break;
            default:
                $newMessages = $this->facebookHelper->retrieveMessageFromData($this->token, $this->content);
                break;
        }
        foreach ($newMessages as $v) {
            $this->om->persist(
                (new FacebookPost())
                    ->setMessage($v['message'])
                    ->setCreated($v['created'])
            );
        }
        $this->client->send('/social-feed', $newMessages);
        $this->om->flush();
        $this->content = null;
    }
}
