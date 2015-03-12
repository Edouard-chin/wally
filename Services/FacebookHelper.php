<?php

namespace SocialWallBundle\Services;

use Facebook\FacebookSession;
use Facebook\FacebookRequest;
use Facebook\FacebookRedirectLoginHelper;
use Facebook\FacebookRequestException;
use Facebook\GraphUser;

use Symfony\Bundle\FrameworkBundle\Routing\Router;

class FacebookHelper
{
    const PAGE_NAME = 'Dudek';

    private $fbAppToken;
    private $router;
    private $secret;

    public function __construct($fbId, $fbSecret, $fbAppToken, $secret, Router $router)
    {
        FacebookSession::setDefaultApplication($fbId, $fbSecret);
        $this->fbAppToken = $fbAppToken;
        $this->router = $router;
        $this->secret = $secret;
    }

    public function retrieveMessageFromData($pageToken, $datas)
    {
        $session = new FacebookSession($pageToken);
        $newMessages = [];
        $datas = json_decode($datas, true);
        foreach ($datas['entry'] as $v) {
            foreach ($v['changes'] as $change) {
                $postId = $change['value']['post_id'];
                $message = (new FacebookRequest(
                    $session,
                    'GET',
                    "/{$postId}"
                ))->execute()->getGraphObject();
                $newMessages[] = $message->getProperty('message');
            }
        }

        return $newMessages;
    }

    // public function getPost()
    // {
    //     $session = new FacebookSession($this->fbAppToken);
    //     $posts = (new FacebookRequest(
    //         $session,
    //         'GET',
    //         '/NormanFaitDesVideos/feed'
    //     ))->execute()->getGraphObjectList();
    //     foreach ($posts as $v) {
    //         $message = $v->getProperty('message');
    //         var_dump($message);
    //     }
    //     exit();
    // }

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
                    $this->subscribeToPage($pageToken, $v->getProperty('id'));
                    return [true, $pageToken];
                }
            }
        }

        return [
            false,
            $helper->getLoginUrl(['public_profile,email,manage_pages']),
        ];
    }

    private function subscribeToPage($pageToken, $pageId)
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
                "/{$pageId}/subscriptions",
                [
                    'object' => 'page',
                    'callback_url' => $this->router->generate('social_wall_facebook_real_time_update', [], true),
                    'fields' => 'feed',
                    'verify_token' => $this->secret,
                ]
            ))->execute()->getGraphObject();
        }
    }
}
