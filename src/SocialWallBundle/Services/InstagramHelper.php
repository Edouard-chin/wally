<?php

namespace SocialWallBundle\Services;

use Buzz\Exception\ClientException;
use Symfony\Component\HttpFoundation\Request;

use SocialWallBundle\Exception\OAuthException;
use SocialWallBundle\Entity\SocialMediaPost;
use SocialWallBundle\Entity\SocialMediaPost\InstagramPost;

class InstagramHelper extends SocialMediaHelper
{
    const API_URI = 'https://api.instagram.com/v1';

    private $clientId;
    private $browser;

    public function __construct($clientId, $clientSecret, \Buzz\Browser $browser = null)
    {
        $this->clientId = $clientId;
        $this->setAppSecret($clientSecret);
        $this->browser = $browser ?: new \Buzz\Browser(new \Buzz\Client\Curl());
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
                return json_decode($response->getContent(), true)['access_token'];
            } else {
                throw new OAuthException($response->getReasonPhrase());
            }
        }

        return "https://api.instagram.com/oauth/authorize/?client_id={$this->clientId}&redirect_uri={$url}&response_type=code";
    }

    public function manualFetch($token, $tag, SocialMediaPost $lastPost = null)
    {
        $parameters = ['access_token' => $token];
        $parameters['min_id'] = $lastPost ? $lastPost->getMinTagId() : '';
        $response = $this->browser->get(self::API_URI . "/tags/{$tag}/media/recent?" . http_build_query($parameters));

        $posts = [];
        $json = json_decode($response->getContent(), true);
        if ($response->isSuccessful()) {
            foreach ($json['data'] as $k => $v) {
                if ($v['type'] == 'image' && array_key_exists('caption', $v)) {
                    $posts[] = (new InstagramPost())
                        ->setMessage($v['caption']['text'])
                        ->setCreated((new \DateTime('@' . $v['created_time']))->setTimeZone(new \DateTimeZone('Europe/Paris')))
                        ->setMinTagId($json['pagination']['min_tag_id'])
                        ->setAuthorUsername($v['user']['full_name'])
                        ->setTag($tag)
                    ;
                }
            }
        }

        return $posts;
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
        if (!$response->isSuccessful()) {
            throw new OAuthException();
        }
    }

    public function removeSubscription($id)
    {
        $response = $this->browser->delete(self::API_URI . '/subscriptions?' . http_build_query([
            'client_secret' => $this->appSecret,
            'client_id'     => $this->clientId,
            'id'            => $id,
        ]));
        if (!$response->isSuccessful()) {
            throw new OAuthException();
        }
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
