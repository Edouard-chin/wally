<?php

namespace SocialWallBundle\Event;

use SocialWallBundle\Event\SocialMediaEvent;
use SocialWallBundle\Entity\SocialMediaPost;

class InstagramEvent extends SocialMediaEvent
{
    private $tag;
    private $lastMessage = null;

    public function __construct($token, $tag, SocialMediaPost $lastMessage = null)
    {
        $this->tag = $tag;
        $this->lastMessage = $lastMessage;
        parent::__construct($token);
    }

    public function getTag()
    {
        return $this->tag;
    }

    public function getLastMessage()
    {
        return $this->lastMessage;
    }
}
