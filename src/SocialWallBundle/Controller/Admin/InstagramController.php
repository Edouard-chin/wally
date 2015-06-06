<?php

namespace SocialWallBundle\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use SocialWallBundle\Exception\OAuthException;
use SocialWallBundle\SocialMediaType;

class InstagramController extends Controller
{
    /**
     * @Route("/login", name="admin_instagram_login")
     */
    public function instagramLoginAction(Request $request)
    {
        $instagramHelper = $this->get('instagram_helper');
        $user = $this->getUser();
        $userManager = $this->get('fos_user.user_manager');

        try {
            $accessToken = $instagramHelper->oAuthHandler($this->generateUrl('admin_instagram_login', [], true), $request);
        } catch (OAuthException $e) {
            $this->addFlash('error', "Nous n'avons pas pu vous identifier, merci de rééssayer.");

            return $this->redirectToRoute('admin_index');
        }
        $user->addAccessToken(SocialMediaType::INSTAGRAM, $accessToken);
        $userManager->updateUser($user);
        $this->addFlash('success', 'Vous êtes bien identifié.');

        return $this->redirectToRoute('admin_index');
    }

    /**
     * @Route("/import/{tag}", name="admin_instagram_import")
     */
    public function importAction(Request $request, $tag)
    {
        $instagramHelper = $this->get('instagram_helper');
        $em = $this->getDoctrine()->getManager();
        $accessToken = $this->getUser()->getAccessTokens();
        if (!isset($accessToken[SocialMediaType::INSTAGRAM])) {
            return $this->redirect($instagramHelper->oAuthHandler($this->generateUrl('admin_instagram_login', [], true), $request));
        }
        $posts = $instagramHelper->manualFetch($accessToken[SocialMediaType::INSTAGRAM], $tag);
        foreach ($posts as $post) {
            usleep(20000);
            $em->persist($post);
        }
        $em->flush();

        return new JsonResponse(['success' => true]);
    }
}
