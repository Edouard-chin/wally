<?php

namespace SocialWallBundle\Services;

use Buzz\Exception\ClientException;
use Symfony\Component\HttpFoundation\Request;

use SocialWallBundle\Exception\OAuthException;
use SocialWallBundle\Entity\SocialMediaPost;

class InstagramHelper extends SocialMediaHelper
{
    const API_URI = 'https://api.instagram.com/v1';

    static $item = [
        'image',
    ];

    private $clientId;
    private $browser;

    public function __construct($clientId, $clientSecret)
    {
        $this->clientId = $clientId;
        $this->setAppSecret($clientSecret);
        $this->browser = new \Buzz\Browser(new \Buzz\Client\Curl());
    }

    public function oAuthHandler($url, Request $request = null)
    {
        if ($code = $request->query->get('code')) {
            $parameters = [
                'client_secret' => $this->appSecret,
                'client_id' => $this->clientId,
                'grant_type' => 'authorization_code',
                'redirect_uri' => $url,
                'code' => $code,
            ];
            $response = $this->browser->submit('https://api.instagram.com/oauth/access_token', $parameters);
            if ($response->isSuccessful()) {
                return [true, json_decode($response->getContent(), true)['access_token']];
            } else {
                throw new OAuthException($response->getReasonPhrase());
            }
        }

        return [
            false,
            "https://api.instagram.com/oauth/authorize/?client_id={$this->clientId}&redirect_uri={$url}&response_type=code"
        ];
    }

    public function searchForRecentTag($tag, $accessToken, SocialMediaPost $lastMessageRetrieved = null)
    {
        $parameters = [
            'access_token' => $accessToken
        ];
        if ($lastMessageRetrieved) {
            $parameters['min_id'] = $lastMessageRetrieved->getMinTagId();
        }

        $response = $this->browser->get(self::API_URI . "/tags/{$tag}/media/recent?" . http_build_query($parameters));
        $newPosts = [];
        $json = json_decode($response->getContent(), true);
        if ($response->isSuccessful()) {
            foreach ($json['data'] as $k => $v) {
                if (in_array($v['type'], self::$item) && array_key_exists('caption', $v)) {
                    $newPosts[$v['id']]['message'] = $v['caption']['text'];
                    $newPosts[$v['id']]['created'] = (new \DateTime('@' . $v['created_time']))->setTimeZone(new \DateTimeZone('Europe/Paris'));
                    $newPosts[$v['id']]['minTagId'] = $json['pagination']['min_tag_id'];
                    $newPosts[$v['id']]['author'] = $v['user']['full_name'];
                    $newPosts[$v['id']]['image'] = $v['images']['standard_resolution']['url'];
                }
            }
        }

        return $newPosts;
    }

    public function addSubscription($callbackUrl, $tag)
    {
        $parameters = [
            'client_id' => $this->clientId,
            'client_secret' => $this->appSecret,
            'object' => 'tag',
            'aspect' => 'media',
            'object_id' => $tag,
            'callback_url' => $callbackUrl,
            'verify_token' => $this->symfonySecret,
        ];

        $response = $this->browser->submit(self::API_URI . '/subscriptions/', $parameters);
    }

    public function removeSubscription($id)
    {
        $response = $this->browser->delete(self::API_URI . '/subscriptions?' . http_build_query([
            'client_secret' => $this->appSecret,
            'client_id'     => $this->clientId,
            'id'            => $id,
        ]));
    }

    public function setSubscriptions($callbackUrl, array $tags)
    {
        $subscribed = $this->getSubscriptions();
        foreach (array_diff($subscribed, $tags) as $key => $v) {
            $this->removeSubscription($key);
        }
        foreach(array_diff($tags, $subscribed) as $v) {
            $this->addSubscription($callbackUrl, $v);
        }
    }

    public function getSubscriptions()
    {
        $url = self::API_URI . '/subscriptions?' . http_build_query([
            'client_secret' => $this->appSecret,
            'client_id'     => $this->clientId,
        ]);
        $response = $this->browser->get($url);
        $subscriptions = [];
        $json = json_decode($response->getContent(), true);
        if (!$response->isSuccessful()) {
            throw new OAuthException($response->getReasonPhrase());
        }

        foreach ($json['data'] as $v) {
            $subscriptions[$v['id']] = $v['object_id'];
        }

        return $subscriptions;
    }
}
