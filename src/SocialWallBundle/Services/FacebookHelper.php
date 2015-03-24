<?php

namespace SocialWallBundle\Services;

use Facebook\FacebookSession;
use Facebook\FacebookRequest;
use Facebook\FacebookRequestException;
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
    private $fbPageName;

    public function __construct($fbId, $fbSecret, $fbAppToken, Session $session, $fbPageName)
    {
        FacebookSession::setDefaultApplication($fbId, $fbSecret);
        $this->fbAppToken = $fbAppToken;
        $this->setAppSecret($fbSecret);
        $this->session = $session;
        $this->fbPageName = $fbPageName;
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

    public function getPost($pageToken, $pageId)
    {
        $session = new FacebookSession($pageToken);
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
     * Admin of a facebook page needs to grant our application to manage his pages.
     * Once done, the subscribeToPage function will make a request to the FB api to get realtime
     * notification when events occurs on the page.
     *
     * @param string $url   An url for facebook callback
     */
    public function oAuthHandler($url, Request $request = null)
    {
        $helper = new FacebookRedirectLoginHelper($url, $this->session);
        $session = $helper->getSessionFromRedirect();
        $isLogged = false;
        if ($session) {
            $userId = (new FacebookRequest(
                $session,
                'GET',
                '/me'
            ))->execute()->getGraphObject(GraphUser::className())->getId();
            $pages = (new FacebookRequest(
                $session,
                'GET',
                "/{$userId}/accounts"
            ))->execute()->getGraphObjectList();
            $isLogged = true;
            foreach ($pages as $v) {
                if ($v->getProperty('name') == $this->fbPageName) {
                    $pageToken = $v->getProperty('access_token');
                    $this->subscribeToPage($pageToken, $v->getProperty('id'));
                    return [$isLogged, $pageToken];
                }
            }
            throw new OAuthException('You are not the admin of the page: ' . $this->fbPageName);
        }

        return [$isLogged, $helper->getLoginUrl(['public_profile,email,manage_pages'])];
    }

    /**
     * Make a request to the FB API to subscribe to the page, then,
     * make another request to subscribe for real time notification on the page.
     *
     * @param string $pageToken    The access token of the page
     */
    private function subscribeToPage($pageToken, $pageId)
    {
        $pageSession = new FacebookSession($pageToken);
        $subscription = (new FacebookRequest(
            $pageSession,
            'POST',
            '/' . $pageId . '/subscribed_apps'
        ))->execute()->getGraphObject();
        if ($subscription->getProperty('success')) {
            $appSession = new FacebookSession($this->fbAppToken);
            $realTimeUpdate = (new FacebookRequest(
                $appSession,
                'POST',
                '/' . $pageId . '/subscriptions',
                [
                    'object' => 'page',
                    'callback_url' => $this->router->generate('social_wall_facebook_real_time_update', [], true),
                    'fields' => 'feed',
                    'verify_token' => $this->symfonySecret,
                ]
            ))->execute()->getGraphObject();
        }
    }
}
