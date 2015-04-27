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
    public function indexAction()
    {
        $facebookHelper = $this->get('facebook_helper');
        if (!$this->get('session')->get('user_access_token')) {
            $this->addFlash('success', '<a href="'.$facebookHelper->oAuthHandler($this->generateUrl('facebook_login', [], true)).'">Clique</a>');
        }

        return $this->render('::Admin/index.html.twig', [
            'facebookPages' => $this->getDoctrine()->getRepository('SocialWallBundle:SocialMediaConfig\FacebookConfig')->findAll()
        ]);
    }
}
