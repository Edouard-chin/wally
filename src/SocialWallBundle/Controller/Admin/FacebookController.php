<?php

namespace SocialWallBundle\Controller\Admin;

use Facebook\FacebookAuthorizationException;
use Facebook\FacebookRequestException;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

use SocialWallBundle\Exception\OAuthException;
use SocialWallBundle\Exception\TokenException;
use SocialWallBundle\Entity\SocialMediaConfig\FacebookConfig;

class FacebookController extends Controller
{
    /**
     * @Route("/login", name="admin_facebook_login")
     */
    public function facebookLoginAction()
    {
        $facebookHelper = $this->get('facebook_helper');

        try {
            $facebookSession = $facebookHelper->oAuthHandler($this->generateUrl('admin_facebook_login', [], true));
            if (!is_object($facebookSession)) {
                return $this->redirectToRoute('admin_index');
            }
            $this->get('session')->set('user_access_token', $facebookSession->getToken());
        } catch (FacebookRequestException $e) {
            $this->addFlash('error', "Une erreur est survenue lors de la connextion à facebook. Code d'erreur: {$e->getHttpStatusCode()}");
        } catch (\Exception $e) {
            $this->addFlash('error', 'Nous n\'avons pas pu vous identifier, merci de rééssayer.');
        }

        return $this->redirectToRoute('admin_index');
    }

    /**
     * @Route("/subscribe", name="admin_facebook_subscribe")
     * @Method({"POST"})
     */
    public function addFacebookSubscriptionAction(Request $request)
    {
        $facebookHelper = $this->get('facebook_helper');
        if (!$accessToken = $this->get('session')->get('user_access_token')) {
            $this->addFlash('error', '<a href="'.$facebookHelper->oAuthHandler($this->generateUrl('admin_facebook_login', [], true)).'">Clique</a>');
        } else {
            try {
                $page = $facebookHelper->addSubscription($this->generateUrl('facebook_real_time_update', [], true), $pageName = $request->request->get('facebook_page'), $accessToken);
                $this->getDoctrine()->getRepository('SocialWallBundle:SocialMediaConfig\FacebookConfig')->updateOrCreatePage($page);
                $this->addFlash('success', "Vous souscrivez maintenant à la page: {$pageName}");
            } catch (TokenException $e) {
                $this->get('session')->remove('user_access_token');
                $this->addFlash('error', '<a href="'.$facebookHelper->oAuthHandler($this->generateUrl('admin_facebook_login', [], true)).'">Clique</a>');
            } catch (FacebookAuthorizationException $e) {
                $this->addFlash('error', 'Something wrong happened');
            } catch (OAuthException $e) {
                $this->addFlash('error', $e->getMessage());
            }
        }

        return $this->redirectToRoute('admin_index');
    }

    /**
     * @Route("/unsubscribe/{pageName}", name="admin_facebook_unsubscribe")
     * @Method({"DELETE", "POST"})
     */
    public function removeFacebookSubscriptionAction(Request $request, FacebookConfig $config)
    {
        if (!$this->isCsrfTokenValid('subscription_remove', $request->request->get('csrf_token'))) {
            throw $this->createAccessDeniedException();
        }
        $facebookHelper = $this->get('facebook_helper');
        try {
            $facebookHelper->removeSubscription($config->getPageId());
            $em = $this->getDoctrine()->getManager();
            $em->remove($config);
            $this->addFlash('success', "Vous ne recevrez désormais plus de mise à jour de la page: {$config->getPageName()}");
            $em->flush();
        } catch (FacebookRequestException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('admin_index');
    }

    /**
     * @Route("/import/{pageName}", name="admin_facebook_import")
     * @Method({"POST"})
     */
    public function importAction(FacebookConfig $page)
    {
        $facebookHelper = $this->get('facebook_helper');
        $posts = $facebookHelper->manualFetch($page->getToken(), $page->getPageId());
        $em = $this->getDoctrine()->getManager();
        foreach ($posts as $post) {
            usleep(25000);
            $em->persist($post);
        }
        $em->flush();

        return new JsonResponse(['success' => true]);
    }
}
