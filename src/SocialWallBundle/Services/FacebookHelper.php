<?php

namespace SocialWallBundle\Services;

use Facebook\Entities\AccessToken;
use Facebook\FacebookSession;
use Facebook\FacebookRequest;
use Facebook\FacebookRequestException;
use Facebook\FacebookAuthorizationException;

use SocialWallBundle\Exception\OAuthException;
use SocialWallBundle\Exception\TokenException;
use SocialWallBundle\Entity\SocialMediaPost;
use SocialWallBundle\Facebook\FacebookRedirectLoginHelper;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;

class FacebookHelper extends SocialMediaHelper implements SocialMediaHelperInterface
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
     * {@inheritDoc}
     */
    public function oAuthHandler($callback, Request $request = null)
    {
        $helper = new FacebookRedirectLoginHelper($callback, $this->session);

        return $helper->getSessionFromRedirect() ?: $helper->getLoginUrl(['public_profile,email,manage_pages']);
    }

    /**
     * {@inheritDoc}
     */
    public function manualFetch($token, $info, SocialMediaPost $lastPost = null)
    {
        $request = new FacebookRequest(
            new FacebookSession($token),
            'GET',
            "/{$info}/feed",
            ['limit' => 250, 'since' => $lastPost ? $lastPost->getCreated()->getTimestamp() : null]
        );
        $posts = $this->recursiveFetch([], $request);

        return $posts;
    }

    /**
     * {@inheritDoc}
     */
    public function addSubscription($callback, $info, $accessToken = null)
    {
        if (!(new AccessToken($accessToken))->isValid()) {
            throw new TokenException();
        }
        if (false === $page = $this->getPageInfo($accessToken, $info)) {
            throw new OAuthException("Unable to get the details for the page: {$info}");
        }
        $userPages = (new FacebookRequest(
            new FacebookSession($accessToken),
            'GET',
            '/me/accounts'
        ))->execute()->getGraphObjectList(\Facebook\GraphPage::className());
        foreach ($userPages as $userPage) {
            if ($userPage->getId() == $page->getId()) {
                try {
                    $this->subscribeToPage($userPage->getProperty('access_token'), $userPage->getId(), $callback);
                } catch (FacebookAuthorizationException $e) {
                    throw new OAuthException("There was a problem trying to subscribe to the page. Error code: {$e->getHttpStatusCode()}");
                }
                return $userPage;
            }
        }

        throw new OAuthException("You are not the admin of the page: {$info}");
    }

    /**
     * {@inheritDoc}
     */
    public function removeSubscription($info)
    {
        $request = (new FacebookRequest(
            new FacebookSession($this->appToken),
            'DELETE',
            "/{$info}/subscribed_apps"
        ))->execute()->getGraphObject();

        return $request->getProperty('success');
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
        return (new SocialMediaPost\FacebookPost())
            ->setMessage($message)
            ->setCreated($created)
            ->setAuthorUsername($authorUsername)
        ;
    }
}
