<?php

namespace SocialWallBundle\Services;

use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

abstract class SocialMediaHelper
{
    protected $appSecret;
    protected $router;
    protected $symfonySecret;

    abstract public function oAuthHandler($url, Request $request = null);

    /**
     * @param string $appSecret  The secret of the application
     */
    public function setAppSecret($appSecret)
    {
        $this->appSecret = $appSecret;
    }

    /**
     * @param Router $router
     */
    public function setRouter(Router $router)
    {
        $this->router = $router;
    }

    /**
     * @param string $symfonySecret   The symfony secret used when subscribing for
     *                                Realtime notification
     */
    public function setSymfonySecret($symfonySecret)
    {
        $this->symfonySecret = $symfonySecret;
    }

    /**
     * @param Request $request
     */
    public function checkPayloadSignature(Request $request, $prefix = '')
    {
        $payloadSignature = $request->headers->get('X-Hub-Signature');
        $realSignature = $prefix . hash_hmac('sha1', $request->getContent(), $this->appSecret);

        return $realSignature == $payloadSignature;
    }

    /**
     * @param Request $request
     * @param Response $response
     */
    public function responseToSubscription(Request $request, Response $response)
    {
        if ($request->query->get('hub_verify_token') == $this->symfonySecret) {
            $response->setContent($request->query->get('hub_challenge'));
            return $response;
        }

        return false;
    }
}
