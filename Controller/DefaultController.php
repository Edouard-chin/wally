<?php

namespace SocialWallBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use SocialWallBundle\Entity\SocialMediaPost\FacebookPost;
use SocialWallBundle\Event\InstagramEvent;
use SocialWallBundle\Event\SocialMediaEvent;
use SocialWallBundle\Exception\OAuthException;
use SocialWallBundle\SocialMediaType;

class DefaultController extends Controller
{
    public function renderSocialUrlsAction(Request $request)
    {
        $instagramHelper = $this->get('instagram_helper');
        $facebookHelper = $this->get('facebook_helper');

        $fbUrl = $facebookHelper->oAuthHandler($this->generateUrl('social_wall_facebook_login', [], true));
        $instagramUrl = $instagramHelper->oAuthHandler($this->generateUrl('social_wall_instagram_login', [], true), $request);

        return $this->render('SocialWallBundle:Default:index.html.twig', [
            'facebookUrl' => $fbUrl,
            'instagramUrl' => $instagramUrl,
        ]);
    }

    public function facebookLoginAction()
    {
        $facebookHelper = $this->get('facebook_helper');

        try {
            $pageToken = $facebookHelper->oAuthHandler($this->generateUrl('social_wall_facebook_login', [], true));
            $em = $this->getDoctrine()->getManager();
            $em->getRepository('SocialWallBundle:AccessToken')->updateOrCreate(SocialMediaType::FACEBOOK, $pageToken);
            $em->flush();
            $this->get('session')->getFlashBag()->add('success', "Vous êtes bien identifié.");
        } catch (\Exception $e) {
            $this->get('session')->getFlashBag()->add('error', "Nous n'avons pas pu vous identifier, merci de rééssayer.");
        }

        exit('all good!');
    }

    public function instagramLoginAction(Request $request)
    {
        $instagramHelper = $this->get('instagram_helper');
        try {
            $accessToken = $instagramHelper->oAuthHandler($this->generateUrl('social_wall_instagram_login', [], true), $request);
        } catch (OAuthException $e) {
            $this->get('session')->getFlashBag()->add('error', "Nous n'avons pas pu vous identifier, merci de rééssayer.");
            return $this->redirect($this->generateUrl('social_wall_render_social_urls'));
        }

        $em = $this->getDoctrine()->getManager();
        $em->getRepository('SocialWallBundle:AccessToken')->updateOrCreate(SocialMediaType::INSTAGRAM, $accessToken);
        $tags = $em->getRepository('SocialWallBundle:SocialMediaConfig')->findOneBy(['type' => SocialMediaType::INSTAGRAM])->getTags();
        $dispatcher = $this->get('event_dispatcher');
        foreach ($tags as $v) {
            $event = new InstagramEvent($accessToken, $v);
            $dispatcher->dispatch(SocialMediaEvent::INSTAGRAM_NEW_DATA, $event);
        }
        $instagramHelper->setSubscriptions($this->generateUrl('social_wall_instagram_real_time_update', [], true), $tags);
        $this->get('session')->getFlashBag()->add('success', "Vous êtes bien identifié.");
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
        }

        return $response;
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
            $dispatcher = $this->get('event_dispatcher');
            foreach (json_decode($request->getContent(), true) as $data) {
                $tag = $data['object_id'];
                $lastMessage = $em->getRepository('SocialWallBundle:SocialMediaPost\InstagramPost')->findOneBy(['tag' => $tag], ['retrieved' => 'DESC'], 1);
                $event = new InstagramEvent($accessToken->getToken(), $tag, $lastMessage);
                $dispatcher->dispatch(SocialMediaEvent::INSTAGRAM_NEW_DATA, $event);
            }
        }

        return $response;
    }
}
