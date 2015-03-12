<?php

namespace SocialWallBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

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
            exit();
        }
    }
}
