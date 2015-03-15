<?php

namespace SocialWallBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use SocialWallBundle\Entity\AccessToken;
use SocialWallBundle\Entity\SocialMediaPost\FacebookPost;
use SocialWallBundle\Entity\SocialMediaPost\InstagramPost;
use SocialWallBundle\Exception\OAuthException;
use SocialWallBundle\Services\SocialMediaHelper;
use SocialWallBundle\SocialMediaType;

use Facebook\FacebookRequestException;

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
        try {
            list($isLogged, $pageToken) = $facebookHelper->oAuthHandler($this->generateUrl('social_wall_facebook_login', [], true));
        } catch (FacebookRequestException $ex) {
            $this->get('session')->getFlashBag()->add('error', "Nous n'avons pas pu vous identifier, merci de rééssayer.");
            $isLogged = false;
        } catch (\Exception $e) {
            $this->get('session')->getFlashBag()->add('error', "Nous n'avons pas pu vous identifier, merci de rééssayer.");
            $isLogged = false;
        }
        if ($isLogged) {
            $em = $this->getDoctrine()->getManager();
            $em->getRepository('SocialWallBundle:AccessToken')->updateOrCreate(SocialMediaType::FACEBOOK, $pageToken);
            $em->flush();
            $this->get('session')->getFlashBag()->add('success', "Vous êtes bien identifié.");
        }

        exit('all good!');
    }

    public function instagramLoginAction(Request $request)
    {
        $instagramHelper = $this->get('instagram_helper');
        try {
            list($isLogged, $accessToken) = $instagramHelper->oAuthHandler(
                $this->generateUrl('social_wall_instagram_login', [], true),
                $request
            );
        } catch (OAuthException $e) {
            $this->get('session')->getFlashBag()->add('error', "Nous n'avons pas pu vous identifier, merci de rééssayer.");
            $isLogged = false;
        }
        if ($isLogged) {
            $em = $this->getDoctrine()->getManager();
            $em->getRepository('SocialWallBundle:AccessToken')->updateOrCreate(SocialMediaType::INSTAGRAM, $accessToken);
            $tags = $em->getRepository('SocialWallBundle:SocialMediaConfig')->findOneBy(['type' => SocialMediaType::INSTAGRAM])->getTags();
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
            $instagramHelper->setSubscriptions($this->generateUrl('social_wall_instagram_real_time_update', [], true), $tags);
            $this->get('session')->getFlashBag()->add('success', "Vous êtes bien identifié.");
            $em->flush();
        }

        exit('all good!');
    }

    public function facebookRealtimeAction(Request $request)
    {
        $response = new Response();
        $facebookHelper = $this->get('facebook_helper');
        if ($request->getMethod() == "GET" && $facebookHelper->responseToSubscription($request, $response)) {
            return $response;
        } elseif ($request->getMethod() == "POST" && $facebookHelper->checkPayloadSignature($request, "sha1=")) {
            $em = $this->getDoctrine()->getManager();
            $pageToken = $em->getRepository('SocialWallBundle:AccessToken')->findOneBy(['type' => SocialMediaType::FACEBOOK]);
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
            $em = $this->getDoctrine()->getManager();
            $accessToken = $em->getRepository('SocialWallBundle:AccessToken')->findOneBy(['type' => SocialMediaType::INSTAGRAM]);
            foreach (json_decode($request->getContent(), true) as $data) {
                $tag = $data['object_id'];
                $lastMessage = $em->getRepository('SocialWallBundle:SocialMediaPost\InstagramPost')->findOneBy(['tag' => $tag], ['retrieved' => 'DESC'], 1);
                foreach ($instagramHelper->searchForRecentTag($tag, $accessToken->getToken(), $lastMessage) as $v) {
                    $em->persist((new InstagramPost())
                        ->setMessage($v['message'])
                        ->setCreated($v['created'])
                        ->setMinTagId($v['minTagId'])
                        ->setAuthorUsername($v['author'])
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
        $tags = $em->getRepository('SocialWallBundle:SocialMediaConfig')->findOneBy(['type' => SocialMediaType::INSTAGRAM])->getTags();
        $instagramHelper = $this->get('instagram_helper');
        $instagramHelper->addSubscription($this->generateUrl('social_wall_instagram_real_time_update', [], true), 'uprodudektest');
        exit('ok!');
        // $instagramHelper->setSubscriptions($this->generateUrl('social_wall_instagram_real_time_update', [], true), $tags);
    }
}
