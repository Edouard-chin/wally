<?php

namespace SocialWallBundle\Services;

use Facebook\FacebookSession;
use Facebook\FacebookRequest;
use Facebook\FacebookRedirectLoginHelper;
use Facebook\FacebookRequestException;
use Facebook\GraphUser;

class FacebookHelper extends SocialMediaHelper
{
    const PAGE_NAME = 'Dudek';
    const PAGE_ID   = 690369934373848;

    static $item = [
        'photo',
        'post',
        'status',
        'comment'
    ];

    private $fbAppToken;

    public function __construct($fbId, $fbSecret, $fbAppToken)
    {
        FacebookSession::setDefaultApplication($fbId, $fbSecret);
        $this->fbAppToken = $fbAppToken;
        $this->fbSecret = $this->setAppSecret($fbSecret);
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
                    'message' => $post->getProperty('message'),
                    'created' => new \DateTime('@' . $v['time']),
                ];
            }
        }

        return $newMessages;
    }

    public function getPost()
    {
        $session = new FacebookSession($this->fbAppToken);
        $messages = [];
        $posts = (new FacebookRequest(
            $session,
            'GET',
            '/' . self::PAGE_ID . '/feed'
        ))->execute()->getGraphObjectList();
        foreach ($posts as $v) {
            $message = $v->getProperty('message');
            if (!$message) {
                continue;
            }
            $newMessages[] = [
                'message' => $v->getProperty('message'),
                'created' => new \DateTime($v->getProperty('get_created')),
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
    public function getUnlimitedAccessToken($url)
    {
        $helper = new FacebookRedirectLoginHelper($url);
        try {
            $session = $helper->getSessionFromRedirect();
        } catch (FacebookRequestException $ex) {
            throw new FacebookRequestException($ex->getMessage());
        } catch (\Exception $ex) {
            throw new \Exception($ex->getMessage());
        }
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
            foreach ($pages as $v) {
                if ($v->getProperty('name') == self::PAGE_NAME) {
                    $pageToken = $v->getProperty('access_token');
                    $this->subscribeToPage($pageToken);
                    return [true, $pageToken];
                }
            }
        }

        return [
            false,
            $helper->getLoginUrl(['public_profile,email,manage_pages']),
        ];
    }

    /**
     * Make a request to the FB API to subscribe to the page, then,
     * make another request to subscribe for real time notification on the page.
     *
     * @param string $pageToken    The access token of the page
     */
    private function subscribeToPage($pageToken)
    {
        $pageSession = new FacebookSession($pageToken);
        $subscription = (new FacebookRequest(
            $pageSession,
            'POST',
            '/' . self::PAGE_ID . '/subscribed_apps'
        ))->execute()->getGraphObject();
        if ($subscription->getProperty('success')) {
            $appSession = new FacebookSession($this->fbAppToken);
            $realTimeUpdate = (new FacebookRequest(
                $appSession,
                'POST',
                '/' . self::PAGE_ID . '/subscriptions',
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
