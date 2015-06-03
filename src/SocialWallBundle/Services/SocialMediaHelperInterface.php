<?php

namespace SocialWallBundle\Services;

use SocialWallBundle\Entity\SocialMediaPost;
use Symfony\Component\HttpFoundation\Request;

interface SocialMediaHelperInterface
{
    public function oAuthHandler($callback, Request $request = null);

    public function addSubscription($callback, $info, $accessToken = null);

    public function removeSubscription($id);

    public function manualFetch($token, $info, SocialMediaPost $lastPost = null);
}
