<?php

namespace SocialWallBundle\Tests\Services;

use SocialWallBundle\Services\InstagramHelper;
use Symfony\Component\HttpFoundation\Request;

class InstagramHelperTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider retrievedSubscriptions
     */
    public function testRetrieveAllSubscriptions($subscriptions)
    {
        $mock = $this->createBuzz(['HTTP/1.1 200 OK'], $subscriptions, 'get');
        $instagramHelper = new InstagramHelper('client_id', 'client_secret', $mock);

        $this->assertCount(4, $instagramHelper->getSubscriptions());
    }

    public function testLoginOnInstagram()
    {
        $mock = $this->createBuzz(['HTTP/1.1 200 OK']);
        $instagramHelper = new InstagramHelper('client_id', 'client_secret', $mock);
        $callbackUrl = 'http://redirect.to';
        $expectedUrl = "https://api.instagram.com/oauth/authorize/?client_id=client_id&redirect_uri={$callbackUrl}&response_type=code";
        $this->assertEquals($expectedUrl, $instagramHelper->oAuthHandler($callbackUrl, new Request()));
    }

    /**
     * @dataProvider retrievedSubscriptions
     */
    public function testPayloadSignature($datas)
    {
        $instagramHelper = new InstagramHelper('client_id', 'client_secret');
        $payloadSignature = hash_hmac('sha1', $datas, 'client_secret');
        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            [],
            $datas
        );
        $request->headers->set('X-Hub-Signature', $payloadSignature);
        $this->assertTrue($instagramHelper->checkPayloadSignature($request));
    }

    /**
     * @dataProvider retrievedSubscriptions
     */
    public function testPayloadSignatureFailure($datas)
    {
        $instagramHelper = new InstagramHelper('client_id', 'client_secret');
        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            [],
            $datas
        );
        $request->headers->set('X-Hub-Signature', 'wrong_signature');
        $this->assertFalse($instagramHelper->checkPayloadSignature($request));
    }

    public function testLoginOnInstagramAfterTheUserApprovedTheApplication()
    {
        $content =
            '{
                "access_token": "my_access_token",
                "user": {
                    "username": "tangfrere",
                    "bio": "",
                    "website": "",
                    "profile_picture": "https://myphoto.jpg",
                    "full_name": "Tang Frere",
                    "id": "123546"
                }
            }'
        ;
        $mock = $this->createBuzz(['HTTP/1.1 200 OK'], $content, 'submit');
        $instagramHelper = new InstagramHelper('client_id', 'client_secret', $mock);
        $callbackUrl = 'http://redirect.to';
        $this->assertEquals('my_access_token', $instagramHelper->oAuthHandler($callbackUrl, new Request(['code' => 'nonce_code'])));
    }

    /**
     * @expectedException SocialWallBundle\Exception\OAuthException
     */
    public function testLoginOnInstagramFailedBecauseOfBadRequest()
    {
        $mock = $this->createBuzz(['HTTP/1.1 400 Bad Request'], null, 'submit');
        $instagramHelper = new InstagramHelper('client_id', 'client_secret', $mock);
        $callbackUrl = 'http://redirect.to';
        $this->assertEquals('my_access_token', $instagramHelper->oAuthHandler($callbackUrl, new Request(['code' => 'nonce_code'])));
    }

    public function retrievedSubscriptions()
    {
        return [
            [
                '{
                  "meta": {
                    "code": 200
                  },
                  "data": [
                    {
                      "object": "tag",
                      "object_id": "upro",
                      "aspect": "media",
                      "callback_url": "http://dudek.ngrok.com/instagram/realtime_update",
                      "type": "subscription",
                      "id": "17233442"
                    },
                    {
                      "object": "tag",
                      "object_id": "dudek",
                      "aspect": "media",
                      "callback_url": "http://dudek.ngrok.com/instagram/realtime_update",
                      "type": "subscription",
                      "id": "17233443"
                    },
                    {
                      "object": "tag",
                      "object_id": "lamernoire",
                      "aspect": "media",
                      "callback_url": "http://dudek.ngrok.com/instagram/realtime_update",
                      "type": "subscription",
                      "id": "17233444"
                    },
                    {
                      "object": "tag",
                      "object_id": "caribouausoleil",
                      "aspect": "media",
                      "callback_url": "http://dudek.ngrok.com/instagram/realtime_update",
                      "type": "subscription",
                      "id": "17248102"
                    }
                  ]
                }'
            ],
        ];
    }

    private function createBuzz(array $headers, $content = null, $method = null)
    {
        $response = new \Buzz\Message\Response;
        $response->setHeaders($headers);
        if ($content) {
            $response->setContent($content);
        }
        $mock = $this->getMockBuilder('Buzz\Browser')
            ->disableOriginalConstructor()
            ->getMock()
        ;
        if ($method) {
            $mock->expects($this->once())
                ->method($method)
                ->will($this->returnValue($response))
            ;
        }

        return $mock;
    }
}
