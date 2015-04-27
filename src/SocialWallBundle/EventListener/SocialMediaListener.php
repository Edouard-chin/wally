<?php

namespace SocialWallBundle\EventListener;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Nc\FayeClient\Client;
use SocialWallBundle\Services\SocialMediaHelper;
use SocialWallBundle\Entity\SocialMediaPost;

class SocialMediaListener
{
    private $socialMediaHelper;
    private $faye;

    public function __construct(SocialMediaHelper $socialMediaHelper, Client $faye)
    {
        $this->socialMediaHelper = $socialMediaHelper;
        $this->faye = $faye;
    }

    public function prePersist(SocialMediaPost $post, LifecycleEventArgs $event)
    {
        $this->faye->send('/messages', [$this->socialMediaHelper->serializeEntity($post)]);
    }
}
