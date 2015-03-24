<?php

namespace SocialWallBundle\Facebook;

use Facebook\FacebookRedirectLoginHelper as BaseClass;
use Symfony\Component\HttpFoundation\Session\Session;

class FacebookRedirectLoginHelper extends BaseClass
{
    private $session;
    private $sessionPrefix = 'FBRLH_';

    public function __construct($redirectUrl, Session $session, $appId = null, $appSecret = null)
    {
        $this->session = $session;
    }

    /**
    * {@inheritDoc}
    */
    protected function storeState($state)
    {
        $this->session->set($this->sessionPrefix . 'state', $state);
    }

    /**
    * {@inheritDoc}
    */
    protected function loadState()
    {
        return $this->session->get($this->sessionPrefix . 'state');
    }
}
