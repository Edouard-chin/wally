<?php

namespace SocialWallBundle\Services;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use SocialWallBundle\Entity\SocialMediaPost;

class SocialMediaHelper
{
    protected $appSecret;
    protected $symfonySecret;

    /**
     * @param string $appSecret The secret of the application
     */
    public function setAppSecret($appSecret)
    {
        $this->appSecret = $appSecret;
    }

    /**
     * @param string $symfonySecret The symfony secret used when subscribing for
     *                              Realtime notification
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
        $realSignature = $prefix.hash_hmac('sha1', $request->getContent(), $this->appSecret);

        return $realSignature == $payloadSignature;
    }

    /**
     * @param Request  $request
     * @param Response $response
     */
    public function responseToSubscription(Request $request, Response $response)
    {
        if ($request->query->get('hub_verify_token') == $this->symfonySecret) {
            $response->setContent($request->query->get('hub_challenge'));

            return true;
        }

        return false;
    }

    /**
     * @param SocialMediaPost $post An instance of SocialMediaPost
     *
     * @return string Json encoded datas
     */
    public function serializeEntity(SocialMediaPost $post)
    {
        $normalizer = (new GetSetMethodNormalizer())
            ->setIgnoredAttributes(['retrieved'])
            ->setCallbacks([
                'created' => function ($value) {
                    return $value instanceof \DateTime ? $value->format('d-m-Y h:i') : '';
                },
            ])
        ;
        $serializer = new Serializer([$normalizer], [new JsonEncoder()]);

        return $serializer->serialize($post, 'json');
    }
}
