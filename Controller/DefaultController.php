<?php

namespace SocialWallBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use SocialWallBundle\Entity\AccessToken;
use SocialWallBundle\Entity\SocialMediaPost\FacebookPost;

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
            $facebookHelper = $this->get('facebook.helper');
            if ($facebookHelper->checkSignature($request)) {
                $em = $this->getDoctrine()->getManager();
                $pageToken = $em->getRepository('SocialWallBundle:AccessToken')->find(1);
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
    }
}
