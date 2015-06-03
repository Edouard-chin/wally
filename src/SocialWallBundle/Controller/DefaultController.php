<?php

namespace SocialWallBundle\Controller;

use Facebook\FacebookRequestException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

use SocialWallBundle\Entity\SocialMediaPost\FacebookPost;
use SocialWallBundle\Event\SocialMediaEvent;
use SocialWallBundle\Exception\OAuthException;
use SocialWallBundle\SocialMediaType;

class DefaultController extends Controller
{
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
            return new StreamedResponse(function () use ($request, $instagramHelper) {
                $em = $this->getDoctrine()->getManager();
                $accesToken = $em->getRepository('SocialWallBundle:SocialMediaConfig\InstagramConfig')->find(1)->getToken();
                foreach (json_decode($request->getContent(), true) as $data) {
                    $tag = $data['object_id'];
                    $lastMessage = $em->getRepository('SocialWallBundle:SocialMediaPost\InstagramPost')->findOneBy(['tag' => $tag], ['retrieved' => 'DESC']);
                    foreach ($instagramHelper->manualFetch($accesToken, $tag, $lastMessage) as $post) {
                        $em->persist($post);
                    }
                }
                $em->flush();
            });
        }

        return $response;
    }

    /**
     * @Route("/", name="index")
     */
    public function indexAction()
    {
        $facebookHelper = $this->get('facebook_helper');
        $posts = array_map(function ($post) use ($facebookHelper) {
            return $facebookHelper->serializeEntity($post);
        }, $this->getDoctrine()->getRepository('SocialWallBundle:SocialMediaPost')->findAll());
        shuffle($posts);

        return $this->render('::Front/index.html.twig', ['posts' => $posts]);
    }
}
