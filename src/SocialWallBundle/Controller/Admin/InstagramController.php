<?php

namespace SocialWallBundle\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
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
            return new JsonResponse(['success' => true]);
        }
        $posts = $instagramHelper->manualFetch($accessToken[SocialMediaType::INSTAGRAM], $tag);
        foreach ($posts as $post) {
            usleep(20000);
            $em->persist($post);
        }
        $em->flush();

        return new JsonResponse(['success' => true]);
    }

    /**
     * @Route("/subscribe", name="admin_instagram_subscribe")
     *
     * @Method({"POST"})
     */
    public function addInstagramSubscriptionAction(Request $request)
    {
        $this->denyAccessUnlessGranted('add_instagram_config', $config);
        $tag = $request->request->get('instagram_tag');
        $instagramHelper = $this->get('instagram_helper');
        $callback = $this->generateUrl('instagram_real_time_update', [], true);
        if ($instagramHelper->addSubscription($callback, $tag)) {
            $instagramConfig = $this->getDoctrine()->getRepository('SocialWallBundle:SocialMediaConfig')->getConfigs([SocialMediaType::INSTAGRAM], $this->getUser(), true);
            $instagramConfig->addTag($tag);
            $this->getDoctrine()->getManager()->flush();
            $this->addFlash('success', "You are now subscribing to the {$tag} hashtag");
        }

        return $this->redirectToRoute('admin_index');
    }

    /**
     * @Route("/remove/{tag}/{token}", name="admin_instagram_unsubscribe")
     */
    public function removeInstagramSubscriptionAction(Request $request, $tag, $token)
    {
        if (!$this->isCsrfTokenValid('remove_subscription', $token)) {
            throw $this->createAccessDeniedException();
        }
        $instagramHelper = $this->get('instagram_helper');
        $instagramConfig = $this->getDoctrine()->getRepository('SocialWallBundle:SocialMediaConfig')->getConfigs([SocialMediaType::INSTAGRAM], $this->getUser(), true);
        if ($instagramHelper->removeSubscription($tag)) {
            $instagramConfig->removeTag($tag);
            $this->getDoctrine()->getManager()->flush();
            $this->addFlash('success', "You unscribed to the tag: {$tag}. You will no longer receive update");
        } else {
            $this->addFlash('error', "We could not unsubscribe to the tag, please try again");
        }

        return $this->redirectToRoute('admin_index');
    }
}
