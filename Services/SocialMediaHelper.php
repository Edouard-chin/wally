<?php

namespace SocialWallBundle\Services;

use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class SocialMediaHelper
{
    protected $appSecret;
    protected $router;
    protected $symfonySecret;

    public function setAppSecret($appSecret)
    {
        $this->appSecret = $appSecret;
    }

    public function setRouter(Router $router)
    {
        $this->router = $router;
    }

    public function setSymfonySecret($symfonySecret)
    {
        $this->symfonySecret = $symfonySecret;
    }

    public function checkPayloadSignature(Request $request)
    {
        $payloadSignature = $request->headers->get('X-Hub-Signature');
        $realSignature = "sha1=" . hash_hmac('sha1', $request->getContent(), $this->appSecret);

        return $realSignature == $payloadSignature;
    }

    public function responseToSubscription(Request $request, Response $response)
    {
        if ($request->query->get('hub_verify_token') == $this->symfonySecret) {
            $response->setContent($request->query->get('hub_challenge'));
            return $response;
        }

        return false;
    }
}
