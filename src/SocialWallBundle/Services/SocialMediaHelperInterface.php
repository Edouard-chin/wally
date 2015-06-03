<?php

namespace SocialWallBundle\Services;

use SocialWallBundle\Entity\SocialMediaPost;
use Symfony\Component\HttpFoundation\Request;

interface SocialMediaHelperInterface
{
    /**
     * Handles the OAuth connection
     *
     * @param string  $callback  The url the media will redirect to when user will approve your app
     * @param Request $request   An instance of Request
     *
     * @return string|object     Can return the login url, an access token, or an object (a FacebookSession for instance)
     */
    public function oAuthHandler($callback, Request $request = null);

    /**
     * Used for the PubSubHubbub protocol, request a new subscription to something
     *
     * @param string $callback    The url the media will request to send data
     * @param string $info        The info you need for subscription (instagram tag, facebook page ...)
     * @param string $accessToken A valid accessToken in case media needs it
     */
    public function addSubscription($callback, $info, $accessToken = null);

    /**
     * Remove a subscription, you will no longer receive update when new event arrives
     *
     * @param string $info The id you need for unsubscribing (instagram tag, facebook page ...)
     *
     * @return boolean   Whether or not the request to unsubscribe was a success
     */
    public function removeSubscription($info);

    /**
     * Use this function if you'd like after subscribing, fetch a whole facebook page to get
     * all comments or retrieves all posts with a particular tag from instagram
     *
     * @param string          $token    A valid accessToken
     * @param string          $info     The info you need to fetch
     * @param SocialMediaPost $lastPost The lastPost from this media stored in db, used to retrieve data after that post
     *
     * @return array An array of SocialMediaPost object
     */
    public function manualFetch($token, $info, SocialMediaPost $lastPost = null);
}
