<?php

namespace SocialWallBundle\Controller\Admin;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class DefaultController extends Controller
{
    /**
     * @Route("/", name="admin_index")
     */
    public function indexAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $instagramConfig = $em->getRepository('SocialWallBundle:SocialMediaConfig\InstagramConfig')->find(1);
        if (!$this->get('session')->get('user_access_token')) {
            $facebookHelper = $this->get('facebook_helper');
            $this->addFlash('success', '<a href="'.$facebookHelper->oAuthHandler($this->generateUrl('admin_facebook_login', [], true)).'">Click to login on FB</a>');
        }
        if (!$instagramConfig->getToken()) {
            $instagramHelper = $this->get('instagram_helper');
            $this->addFlash('success', '<a href="'.$instagramHelper->oAuthHandler($this->generateUrl('admin_instagram_login', [], true), $request).'">Click to login on Instagram</a>');
        }

        return $this->render('::Admin/index.html.twig', [
            'facebookPages' => $em->getRepository('SocialWallBundle:SocialMediaConfig\FacebookConfig')->findAll(),
            'instagramTags' => $instagramConfig->getTags(),
        ]);
    }
}
