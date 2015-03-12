<?php

namespace SocialWallBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use SocialWallBundle\Entity\AccessToken;

class DefaultController extends Controller
{
    public function facebookLoginAction()
    {
        $facebookHelper = $this->get('facebook.helper');
        list($isLogged, $data) = $facebookHelper->getUnlimitedAccessToken($this->generateUrl('social_wall_facebook_login', [], true));
        if (!$isLogged) {
            return $this->render('SocialWallBundle:Default:index.html.twig', [
                'url' => $data,
            ]);
        } else {
            $em = $this->getDoctrine()->getManager();
            $accessToken = (new AccessToken())
                ->setToken($data)
            ;
            $em->persist($accessToken);
            $em->flush();
        }
    }

    public function realtimeUpdateAction(Request $request)
    {
        $response = new Response();
        if ($request->getMethod() == "GET") {
            $secret = $this->container->getParameter('secret');
            if ($request->query->get('hub_verify_token') == $secret) {
                $response->setContent($request->query->get('hub_challenge'));
                return $response;
            }
        } elseif ($request->getMethod() == "POST") {
            $appSecret = $this->container->getParameter('facebook_secret');
            $fbSignature = $request->headers->get('X-Hub-Signature');
            $checkSignature = "sha1=" . hash_hmac('sha1', $request->getContent(), $appSecret);
            if ($fbSignature == $checkSignature) {
                $em = $this->getDoctrine()->getManager();
                $pageToken = $em->getRepository('SocialWallBundle:AccessToken')->find(1);
                $facebookHelper = $this->get('facebook.helper');
                $newMessages = $facebookHelper->retrieveMessageFromData($pageToken->getToken(), $request->getContent());
                exit($newMessages);
            }
        }
    }
}
