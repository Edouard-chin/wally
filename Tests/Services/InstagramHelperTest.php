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
        $response = new \Buzz\Message\Response;
        $response->setContent($subscriptions);
        $response->setHeaders(['HTTP/1.1 200 OK']);
        $mock = $this->getMockBuilder('Buzz\Browser')
            ->disableOriginalConstructor()
            ->getMock()
        ;
        $mock->expects($this->once())
            ->method('get')
            ->will($this->returnValue($response))
        ;
        $instagramHelper = new InstagramHelper('client_id', 'client_secret', $mock);

        $this->assertCount(4, $instagramHelper->getSubscriptions());
    }

    public function testLoginOnInstagram()
    {
        $response = new \Buzz\Message\Response;
        $response->setHeaders(['HTTP/1.1 200 OK']);
        $mock = $this->getMockBuilder('Buzz\Browser')
            ->disableOriginalConstructor()
            ->getMock()
        ;
        $instagramHelper = new InstagramHelper('client_id', 'client_secret', $mock);
        $callbackUrl = 'http://redirect.to';
        $expectedUrl = "https://api.instagram.com/oauth/authorize/?client_id=client_id&redirect_uri={$callbackUrl}&response_type=code";
        $this->assertEquals($expectedUrl, $instagramHelper->oAuthHandler($callbackUrl, new Request()));
    }

    public function testLoginOnInstagramAfterTheUserApprovedTheApplication()
    {
        $response = new \Buzz\Message\Response;
        $response->setHeaders(['HTTP/1.1 200 OK']);
        $response->setContent(
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
        );
        $mock = $this->getMockBuilder('Buzz\Browser')
            ->disableOriginalConstructor()
            ->getMock()
        ;
        $mock->expects($this->once())
            ->method('submit')
            ->will($this->returnValue($response))
        ;
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
}
