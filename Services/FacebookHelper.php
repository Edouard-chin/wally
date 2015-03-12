<?php

namespace SocialWallBundle\Services;

use Facebook\FacebookSession;
use Facebook\FacebookRequest;
use Facebook\FacebookRedirectLoginHelper;
use Facebook\FacebookRequestException;
use Facebook\GraphUser;

class FacebookHelper
{
    private $fbId;
    private $fbSecret;

    public function __construct($fbId, $fbSecret)
    {
        FacebookSession::setDefaultApplication($fbId, $fbSecret);
        $this->fbId = $fbId;
        $this->fbSecret = $fbSecret;
    }

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
            try {
                $userId = (new FacebookRequest(
                    $session,
                    'GET',
                    '/me'
                ))->execute()->getGraphObject(GraphUser::className())->getId();
                $pages = (new FacebookRequest(
                    $session,
                    'GET',
                    "/{$userId}/accounts"
                ))->execute()->getGraphObject(GraphUser::className());
                var_dump($pages);exit();
                $longLivedAccessToken = $session->getAccessToken()->extend();
                return [true, $longLivedAccessToken];
            } catch (FacebookRequestException $ex) {
            }
        }

        return [
            false,
            $helper->getLoginUrl(['public_profile,email,manage_pages']),
        ];
    }
}
