<?php

namespace SocialWallBundle\Services;

use Facebook\Entities\AccessToken;
use Facebook\FacebookSession;
use Facebook\FacebookRequest;
use Facebook\FacebookRequestException;
use Facebook\FacebookAuthorizationException;

use SocialWallBundle\Exception\OAuthException;
use SocialWallBundle\Exception\TokenException;
use SocialWallBundle\Entity\SocialMediaPost\FacebookPost;
use SocialWallBundle\Facebook\FacebookRedirectLoginHelper;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;

class FacebookHelper extends SocialMediaHelper
{
    private $appToken;
    private $session;

    public function __construct($appId, $appSecret, $appToken, Session $session)
    {
        FacebookSession::setDefaultApplication($appId, $appSecret);
        $this->appToken = $appToken;
        $this->session = $session;
        $this->setAppSecret($appSecret);
    }

    /**
     * @param string $datas           JSON encoded datas
     * @return array FacebookPost
     */
    public function updateHandler($datas)
    {
        $posts = [];
        $datas = json_decode($datas);
        foreach ($datas->entry as $v) {
            $feed = $v->changes[0]->value;
            if (!isset($feed->message)) {
                continue;
            }
            $posts[] = $this->createPostEntities(
                $feed->message,
                (new \DateTime('@'.$v->time))->setTimeZone(new \DateTimeZone('Europe/Paris')),
                $feed->sender_name
            );
        }

        return $posts;
    }

    /**
     *  @param  string       $token      A facebook AccessToken, can be a page or user token
     *  @param  string       $pageId     The Id of the facebook page
     *  @param  FacebookPost $lastPost
     *  @return array FacebookPost
     */
    public function manualFetch($token, $pageId, FacebookPost $lastPost = null)
    {
        $request = new FacebookRequest(
            new FacebookSession($token),
            'GET',
            "/{$pageId}/feed",
            ['limit' => 250, 'since' => $lastPost ? $lastPost->getCreated()->getTimestamp() : null]
        );
        $posts = $this->recursiveFetch([], $request);

        return $posts;
    }

    /**
     * @param string $url   An url for facebook callback
     */
    public function oAuthHandler($url, Request $request = null)
    {
        $helper = new FacebookRedirectLoginHelper($url, $this->session);

        return $helper->getSessionFromRedirect() ?: $helper->getLoginUrl(['public_profile,email,manage_pages']);
    }

    /**
     * @param string $userAccessToken    A Facebook User Access Token
     * @param string $callbackUrl        The url where facebook will send data to
     * @param string $pageName           The name of facebook page to subscribe to
     */
    public function addSubscription($userAccessToken, $callbackUrl, $pageName)
    {
        if (!(new AccessToken($userAccessToken))->isValid()) {
            throw new TokenException();
        }
        if (false === $page = $this->getPageInfo($userAccessToken, $pageName)) {
            throw new OAuthException("Unable to get the details for the page: {$pageName}");
        }
        $userPages = (new FacebookRequest(
            new FacebookSession($userAccessToken),
            'GET',
            '/me/accounts'
        ))->execute()->getGraphObjectList(\Facebook\GraphPage::className());
        foreach ($userPages as $userPage) {
            if ($userPage->getId() == $page->getId()) {
                try {
                    $this->subscribeToPage($userPage->getProperty('access_token'), $userPage->getId(), $callbackUrl);
                } catch (FacebookAuthorizationException $e) {
                    throw new OAuthException("There was a problem trying to subscribe to the page. Error code: {$e->getHttpStatusCode()}");
                }
                return $userPage;
            }
        }

        throw new OAuthException("You are not the admin of the page: {$pageName}");
    }

    /**
     * @param string $pageId       The facebook pageId
     * @return boolean
     */
    public function removeSubscription($pageId)
    {
        $request = (new FacebookRequest(
            new FacebookSession($this->appToken),
            'DELETE',
            "/{$pageId}/subscribed_apps"
        ))->execute()->getGraphObject();

        return $request->getProperty('success');
    }

    /**
     * @param string $token      A facebook access token
     * @param string $pageName   The name of the facebook page
     * @return GraphPage
     */
    public function getPageInfo($token, $pageName)
    {
        try {
            $page = (new FacebookRequest(
                new FacebookSession($token),
                'GET',
                "/{$pageName}"
            ))->execute()->getGraphObject(\Facebook\GraphPage::className());
        } catch (FacebookAuthorizationException $e) {
            return false;
        }

        return $page;
    }

    /**
     * Make a request to the FB API to subscribe to the page, then,
     * make another request to subscribe for real time notification on the page.
     *
     * @param string $pageToken    The access token of the page
     */
    private function subscribeToPage($pageToken, $pageId, $callbackUrl)
    {
        $subscription = (new FacebookRequest(
            new FacebookSession($pageToken),
            'POST',
            "/{$pageId}/subscribed_apps"
        ))->execute()->getGraphObject();
        if ($subscription->getProperty('success')) {
            (new FacebookRequest(
                new FacebookSession($this->appToken),
                'POST',
                "/{$pageId}/subscriptions",
                [
                    'object' => 'page',
                    'callback_url' => $callbackUrl,
                    'fields' => 'feed',
                    'verify_token' => $this->symfonySecret,
                ]
            ))->execute()->getGraphObject();
        }
    }

    private function recursiveFetch(array $posts, FacebookRequest $request)
    {
        $response = $request->execute();
        $data = $response->getGraphObjectList();
        foreach ($data as $v) {
            if (!$message = $v->getProperty('message')) {
                continue;
            }
            $posts[] = $this->createPostEntities(
                $message,
                (new \DateTime($v->getProperty('created_time')))->setTimeZone(new \DateTimeZone('Europe/Paris')),
                $v->getProperty('from')->getProperty('name')
            );
        }
        if ($nextRequest = $response->getRequestForNextPage()) {
            $this->recursiveFetch($posts, $nextRequest);
        }

        return $posts;
    }

    private function createPostEntities($message, \DateTime $created, $authorUsername)
    {
        return (new FacebookPost())
            ->setMessage($message)
            ->setCreated($created)
            ->setAuthorUsername($authorUsername)
        ;
    }
}
