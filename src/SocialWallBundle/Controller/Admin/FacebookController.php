<?php

namespace SocialWallBundle\Controller\Admin;

use Facebook\FacebookAuthorizationException;
use Facebook\FacebookRequestException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use SocialWallBundle\Exception\OAuthException;
use SocialWallBundle\Exception\TokenException;
use SocialWallBundle\Entity\SocialMediaConfig\FacebookConfig;
use SocialWallBundle\SocialMediaType;

class FacebookController extends Controller
{
    /**
     * @Route("/login", name="admin_facebook_login")
     */
    public function facebookLoginAction()
    {
        $facebookHelper = $this->get('facebook_helper');
        $user = $this->getUser();
        $userManager = $this->get('fos_user.user_manager');
        $translator = $this->get('translator');

        try {
            $facebookSession = $facebookHelper->oAuthHandler($this->generateUrl('admin_facebook_login', [], true));
            if (!is_object($facebookSession)) {
                return $this->redirectToRoute('admin_index');
            }
            $user->addAccessToken(SocialMediaType::FACEBOOK, $facebookSession->getToken());
        } catch (FacebookRequestException $e) {
            $this->addFlash('error', $translator->trans('admin.flash.facebook.connexion_error', ['%code%' => $e->getHttpStatusCode()]));
        } catch (\Exception $e) {
            $this->addFlash('error', $translator->trans('admin.flash.facebook.authentication_fails'));
        }
        $userManager->updateUser($user);

        return $this->redirectToRoute('admin_index');
    }

    /**
     * @Route("/subscribe", name="admin_facebook_subscribe")
     *
     * @Method({"POST"})
     */
    public function addFacebookSubscriptionAction(Request $request)
    {
        $this->denyAccessUnlessGranted('add_facebook_config', $user = $this->getUser());
        $facebookHelper = $this->get('facebook_helper');
        $accessToken = $user->getAccessTokens();
        $translator = $this->get('translator');

        if (!isset($accessToken[SocialMediaType::FACEBOOK])) {
            $this->addFlash('error', '<a href="'.$facebookHelper->oAuthHandler($this->generateUrl('admin_facebook_login', [], true)).'">Clique</a>');
        } else {
            try {
                $page = $facebookHelper->addSubscription($this->generateUrl('facebook_real_time_update', [], true), $pageName = $request->request->get('facebook_page'), $accessToken[SocialMediaType::FACEBOOK]);
                $config = $this->getDoctrine()->getRepository('SocialWallBundle:SocialMediaConfig\FacebookConfig')->updateOrCreatePage($page);
                $this->addFlash('success', $translator->trans('admin.flash.facebook.page_subscribe_success', ['%pageName%' => $pageName]));
                $user->addSocialMediaConfig($config);
                $this->getDoctrine()->getManager()->flush();
            } catch (TokenException $e) {
                $this->addFlash('error', $translator->trans('admin.flash.login', [
                    '%url%' => '<a href="'.$facebookHelper->oAuthHandler($this->generateUrl('admin_facebook_login', [], true)).'">Here</a>',
                    '%media%' => 'facebook'
                ]));
            } catch (FacebookAuthorizationException $e) {
                $this->addFlash('error', 'Something wrong happened');
            } catch (OAuthException $e) {
                $this->addFlash('error', $e->getMessage());
            }
        }

        return $this->redirectToRoute('admin_index');
    }

    /**
     * @Route("/unsubscribe/{pageName}/{token}", name="admin_facebook_unsubscribe")
     * @ParamConverter("config", options={"mapping": {"pageName": "pageName"}})
     */
    public function removeFacebookSubscriptionAction(Request $request, FacebookConfig $config, $token)
    {
        $this->denyAccessUnlessGranted('remove_facebook_config', $config);
        if (!$this->isCsrfTokenValid('remove_subscription', $token)) {
            throw $this->createAccessDeniedException();
        }
        $translator = $this->get('translator');
        $facebookHelper = $this->get('facebook_helper');
        try {
            if ($facebookHelper->removeSubscription($config->getPageId())) {
                $em = $this->getDoctrine()->getManager();
                $em->remove($config);
                $this->addFlash('success', $translator->trans('admin.flash.facebook.page_unsubscribe_success', ['%pageName%' => $config->getPageName()]));
                $em->flush();
            }
        } catch (FacebookRequestException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('admin_index');
    }

    /**
     * @Route("/import/{pageName}", name="admin_facebook_import")
     *
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
