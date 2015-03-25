<?php

namespace SocialWallBundle\Event;

use SocialWallBundle\Event\SocialMediaEvent;
use SocialWallBundle\Entity\SocialMediaPost;

class FacebookEvent extends SocialMediaEvent
{
    private $content;
    private $type = null;

    public function __construct($token, $type = 'fetch', $content = null)
    {
        $this->content = $content;
        $this->type = $type;
        parent::__construct($token);
    }

    public function getContent()
    {
        return $this->content;
    }

    public function getType()
    {
        return $this->type;
    }
}
