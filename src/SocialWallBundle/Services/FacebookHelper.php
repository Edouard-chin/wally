<?php

namespace SocialWallBundle\Services;

use Facebook\Entities\AccessToken;
use Facebook\FacebookSession;
use Facebook\FacebookRequest;
use Facebook\FacebookRequestException;
use Facebook\FacebookAuthorizationException;
use Facebook\GraphUser;

use SocialWallBundle\Exception\OAuthException;
use SocialWallBundle\Facebook\FacebookRedirectLoginHelper;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;

class FacebookHelper extends SocialMediaHelper
{
    static $item = [
        'photo',
        'post',
        'status',
        'comment'
    ];

    private $fbAppToken;
    private $session;

    public function __construct($fbId, $fbSecret, $fbAppToken, Session $session)
    {
        FacebookSession::setDefaultApplication($fbId, $fbSecret);
        $this->fbAppToken = $fbAppToken;
        $this->setAppSecret($fbSecret);
        $this->session = $session;
    }

    /**
     * @param string $pageToken   The access token of the page
     * @param string $datas       JSON encoded datas
     */
    public function retrieveMessageFromData($pageToken, $datas)
    {
        $session = new FacebookSession($pageToken);
        $newMessages = [];
        $datas = json_decode($datas, true);
        foreach ($datas['entry'] as $v) {
            foreach ($v['changes'] as $change) {
                if (!in_array($change['value']['item'], self::$item) || $change['value']['verb'] != 'add') {
                    continue ;
                }
                $postId = array_key_exists('post_id', $change['value']) ? $change['value']['post_id'] : $change['value']['comment_id'];
                try {
                    $post = (new FacebookRequest(
                        $session,
                        'GET',
                        "/{$postId}"
                    ))->execute()->getGraphObject();
                } catch (FacebookRequestException $e) {
                    continue;
                }

                $message = $post->getProperty('message');
                if (!$message) {
                    continue;
                }
                $newMessages[] = [
                    'message' => $message,
                    'created' => (new \DateTime('@' . $v['time']))->setTimeZone(new \DateTimeZone('Europe/Paris')),
                ];
            }
        }

        return $newMessages;
    }

    public function getOlderPosts($pageToken)
    {
        $session = new FacebookSession($pageToken);
        $pageId = (new FacebookRequest(
            $session,
            'GET',
            "/debug_token",
            ['input_token' => $pageToken]
        ))->execute()->getGraphObject()->getProperty('profile_id');
        $messages = [];
        $posts = (new FacebookRequest(
            $session,
            'GET',
            '/' . $pageId . '/feed'
        ))->execute()->getGraphObjectList();
        foreach ($posts as $v) {
            $message = $v->getProperty('message');
            if (!$message) {
                continue;
            }
            $newMessages[] = [
                'message' => $v->getProperty('message'),
                'created' => (new \DateTime($v->getProperty('created_time')))->setTimeZone(new \DateTimeZone('Europe/Paris')),
            ];
        }

        return $newMessages;
    }

    /**
     * @param string $url   An url for facebook callback
     */
    public function oAuthHandler($url, Request $request = null)
    {
        $helper = new FacebookRedirectLoginHelper($url, $this->session);

        return $helper->getSessionFromRedirect() ?: $helper->getLoginUrl(['public_profile,email,manage_pages']);
    }

    public function addSubscription($userAccessToken, $callbackUrl, $pageName)
    {
        if (!(new AccessToken($userAccessToken))->isValid()) {
            throw new FacebookAuthorizationException();
        }
        $session = new FacebookSession($userAccessToken);
        $pages = (new FacebookRequest(
            $session,
            'GET',
            "/me/accounts"
        ))->execute()->getGraphObjectList(\Facebook\GraphPage::className());
        foreach ($pages as $page) {
            if ($page->getId() == getPageInfo($userAccessToken, $pageName)->getId()) {
                $this->subscribeToPage($page->getProperty('access_token'), $page->getId(), $callbackUrl);
                return $page;
            }
        }

        throw new OAuthException("You are not the admin of the page: {$page}");
    }

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
        $pageSession = new FacebookSession($pageToken);
        $subscription = (new FacebookRequest(
            $pageSession,
            'POST',
            "/{$pageId}/subscribed_apps"
        ))->execute()->getGraphObject();
        if ($subscription->getProperty('success')) {
            $appSession = new FacebookSession($this->fbAppToken);
            $realTimeUpdate = (new FacebookRequest(
                $appSession,
                'POST',
                '/' . $pageId . '/subscriptions',
                [
                    'object' => 'page',
                    'callback_url' => $callbackUrl,
                    'fields' => 'feed',
                    'verify_token' => $this->symfonySecret,
                ]
            ))->execute()->getGraphObject();
        }
    }
}
