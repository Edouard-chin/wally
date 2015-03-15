<?php

namespace SocialWallBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use SocialWallBundle\Entity\AccessToken;
use SocialWallBundle\Entity\SocialMediaPost\FacebookPost;
use SocialWallBundle\Entity\SocialMediaPost\InstagramPost;
use SocialWallBundle\Services\SocialMediaHelper;

class DefaultController extends Controller
{
    public function renderSocialUrlsAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        $instagramHelper = $this->get('instagram_helper');
        $facebookHelper = $this->get('facebook_helper');
        list($isLoggedOnFb, $fbUrl) = $facebookHelper->oAuthHandler(
            $this->generateUrl('social_wall_facebook_login', [], true)
        );
        list($isLoggedOnInstagram, $instagramUrl) = $instagramHelper->oAuthHandler(
            $this->generateUrl('social_wall_instagram_login', [], true),
            $request
        );
        if (!$isLoggedOnFb && !$isLoggedOnInstagram) {
            return $this->render('SocialWallBundle:Default:index.html.twig', [
                'facebookUrl' => $fbUrl,
                'instagramUrl' => $instagramUrl,
            ]);
        }
        exit('All good');
    }

    public function facebookLoginAction()
    {
        $facebookHelper = $this->get('facebook_helper');
        list($isLogged, $pageToken) = $facebookHelper->oAuthHandler($this->generateUrl('social_wall_facebook_login', [], true));
        if ($isLogged) {
            $em = $this->getDoctrine()->getManager();
            $token = (new AccessToken())
                ->setToken($pageToken)
                ->setTtype(AccessToken::TYPE_FACEBOOK)
            ;
            $em->persist($token);
            $em->flush();
        }
        exit('all good!');
    }

    public function instagramLoginAction(Request $request)
    {
        $instagramHelper = $this->get('instagram_helper');
        list($isLogged, $accessToken) = $instagramHelper->oAuthHandler(
            $this->generateUrl('social_wall_instagram_login', [], true),
            $request
        );
        if ($isLogged) {
            $em = $this->getDoctrine()->getManager();
            $token = (new AccessToken())
                ->setToken($accessToken)
                ->setTtype(AccessToken::TYPE_INSTAGRAM)
            ;
            $em->persist($token);
            $em->flush();
            $tags = $em->getRepository('SocialWallBundle:InstagramConfig')->find(1)->getTags();
            foreach ($tags as $v) {
                foreach ($instagramHelper->searchForRecentTag($v, $accessToken) as $newPost) {
                    $em->persist((new InstagramPost())
                        ->setMessage($newPost['message'])
                        ->setCreated($newPost['created'])
                        ->setMinTagId($newPost['minTagId'])
                        ->setTag($v)
                    );
                }
            }
        }
        $em->flush();
        exit('all good!');
    }

    public function realtimeUpdateAction(Request $request)
    {
        $response = new Response();
        $facebookHelper = $this->get('facebook_helper');
        if ($request->getMethod() == "GET" && $facebookHelper->responseToSubscription($request, $response)) {
            return $response;
        } elseif ($request->getMethod() == "POST" && $facebookHelper->checkPayloadSignature($request, "sha1=")) {
            $em = $this->getDoctrine()->getManager();
            $pageToken = $em->getRepository('SocialWallBundle:AccessToken')->findOneBy(['type' => AccessToken::TYPE_FACEBOOK]);
            $newMessages = $facebookHelper->retrieveMessageFromData($pageToken->getToken(), $request->getContent());
            foreach ($newMessages as $v) {
                $em->persist((new FacebookPost())
                    ->setMessage($v['message'])
                    ->setCreated($v['created'])
                );
            }
            $em->flush();
            return $response;
        }
    }

    public function instagramRealTimeAction(Request $request)
    {
        $response = new Response();
        $instagramHelper = $this->get('instagram_helper');
        if ($request->getMethod() == "GET" && $instagramHelper->responseToSubscription($request, $response)) {
            return $response;
        } elseif ($request->getMethod() == "POST" && $instagramHelper->checkPayloadSignature($request)) {
            $datas = json_decode($request->getContent(), true);
            $em = $this->getDoctrine()->getManager();
            $accessToken = $em->getRepository('SocialWallBundle:AccessToken')->findOneBy(['type' => AccessToken::TYPE_INSTAGRAM]);
            foreach ($datas as $data) {
                $tag = $data['object_id'];
                $lastMessageRetrieved = $em->getRepository('SocialWallBundle:SocialMediaPost\InstagramPost')->findOneBy(['tag' => $tag], ['minTagId' => 'DESC'], 1);
                foreach ($instagramHelper->searchForRecentTag($tag, $accessToken->getToken(), $lastMessageRetrieved) as $v) {
                    $em->persist((new InstagramPost())
                        ->setMessage($v['message'])
                        ->setCreated($v['created'])
                        ->setMinTagId($v['minTagId'])
                        ->setTag($tag)
                    );
                }
            }

            $em->flush();
        }
        return $response;
    }

    public function subscribeAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $tags = $em->getRepository('SocialWallBundle:InstagramConfig')->find(1)->getTags();
        $instagramHelper = $this->get('instagram_helper');
        $instagramHelper->addSubscription($this->generateUrl('social_wall_instagram_real_time_update', [], true), 'nofilter');
        exit('ok!');
        // $instagramHelper->setSubscriptions($this->generateUrl('social_wall_instagram_real_time_update', [], true), $tags);
    }
}
