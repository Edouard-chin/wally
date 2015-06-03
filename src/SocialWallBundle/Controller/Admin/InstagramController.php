<?php

namespace SocialWallBundle\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use SocialWallBundle\Exception\OAuthException;

class InstagramController extends Controller
{
    /**
     * @Route("/login", name="admin_instagram_login")
     */
    public function instagramLoginAction(Request $request)
    {
        $instagramHelper = $this->get('instagram_helper');
        try {
            $accessToken = $instagramHelper->oAuthHandler($this->generateUrl('admin_instagram_login', [], true), $request);
        } catch (OAuthException $e) {
            $this->addFlash('error', "Nous n'avons pas pu vous identifier, merci de rééssayer.");
            return $this->redirectToRoute('admin_index');
        }

        $em = $this->getDoctrine()->getManager();
        $instagramConfig = $em->getRepository('SocialWallBundle:SocialMediaConfig\InstagramConfig')->find(1);
        $instagramConfig->setToken($accessToken);
        $em->flush();

        try {
            $instagramHelper->setSubscriptions($this->generateUrl('instagram_real_time_update', [], true), $instagramConfig->getTags());
        } catch (OAuthException $e) {
            $this->addFlash('error', "Nous n'avons pas pu souscrire aux tags");
        }
        $this->addFlash('success', "Vous êtes bien identifié.");

        return $this->redirectToRoute('admin_index');
    }

    /**
     * @Route("/import/{tag}", name="admin_instagram_import")
     */
    public function importAction(Request $request, $tag)
    {
        $instagramHelper = $this->get('instagram_helper');
        $em = $this->getDoctrine()->getManager();
        $instagramConfig = $em->getRepository('SocialWallBundle:SocialMediaConfig\InstagramConfig')->find(1);
        if (null === $accessToken = $instagramConfig->getToken()) {
            return $this->redirect($instagramHelper->oAuthHandler($this->generateUrl('admin_instagram_login', [], true), $request));
        }
        $posts = $instagramHelper->manualFetch($accessToken, $tag);
        foreach ($posts as $post) {
            usleep(20000);
            $em->persist($post);
        }
        $em->flush();

        return new JsonResponse(['success' => true]);
    }
}
