<?php

namespace SocialWallBundle\Controller;

use Facebook\FacebookRequestException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

use SocialWallBundle\Entity\SocialMediaPost\FacebookPost;
use SocialWallBundle\Event\InstagramEvent;
use SocialWallBundle\Event\FacebookEvent;
use SocialWallBundle\Event\SocialMediaEvent;
use SocialWallBundle\Exception\OAuthException;
use SocialWallBundle\SocialMediaType;

class DefaultController extends Controller
{
    /**
     * @Route("/instagram/login", name="instagram_login")
     */
    public function instagramLoginAction(Request $request)
    {
        $instagramHelper = $this->get('instagram_helper');
        try {
            $accessToken = $instagramHelper->oAuthHandler($this->generateUrl('instagram_login', [], true), $request);
        } catch (OAuthException $e) {
            $this->addFlash('error', "Nous n'avons pas pu vous identifier, merci de rééssayer.");
            return $this->redirectToRoute('render_social_urls');
        }

        $em = $this->getDoctrine()->getManager();
        $em->getRepository('SocialWallBundle:AccessToken')->updateOrCreate(SocialMediaType::INSTAGRAM, $accessToken);
        $tags = $em->getRepository('SocialWallBundle:SocialMediaConfig')->findOneBy(['type' => SocialMediaType::INSTAGRAM])->getTags();
        $dispatcher = $this->get('event_dispatcher');
        foreach ($tags as $v) {
            $event = new InstagramEvent($accessToken, $v);
            $dispatcher->dispatch(SocialMediaEvent::INSTAGRAM_NEW_DATA, $event);
        }
        try {
            $instagramHelper->setSubscriptions($this->generateUrl('instagram_real_time_update', [], true), $tags);
        } catch (OAuthException $e) {
            $this->addFlash('error', "Nous n'avons pas pu souscrire aux tags");
        }
        $this->addFlash('success', "Vous êtes bien identifié.");
    }

    /**
     * @Route("/facebook/realtime_update", name="facebook_real_time_update")
     */
    public function facebookRealtimeAction(Request $request)
    {
        $response = new Response();
        $facebookHelper = $this->get('facebook_helper');

        if ($request->getMethod() == "GET" && $facebookHelper->responseToSubscription($request, $response)) {
            return $response;
        } elseif ($request->getMethod() == "POST" && $facebookHelper->checkPayloadSignature($request, "sha1=")) {
            return new StreamedResponse(function () use ($request, $facebookHelper) {
                $em = $this->getDoctrine()->getManager();
                $posts = $facebookHelper->updateHandler($request->getContent());
                foreach ($posts as $post) {
                    $em->persist($post);
                }
                $em->flush();
            });
        }

        return $response;
    }

    /**
     * @Route("/instagram/realtime_update", name="instagram_real_time_update")
     */
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
