<?php

namespace SocialWallBundle\Event;

use Symfony\Component\EventDispatcher\Event;

class SocialMediaEvent extends Event
{
    const FACEBOOK_NEW_DATA  = 'social_media.facebook.new_data';
    const INSTAGRAM_NEW_DATA = 'social_media.instagram.new_data';

    private $token;

    public function __construct($token)
    {
        $this->token = $token;
    }

    public function getToken()
    {
        return $this->token;
    }
}
