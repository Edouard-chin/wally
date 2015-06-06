<?php

namespace SocialWallBundle\Controller\Admin;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use SocialWallBundle\SocialMediaType;

class DefaultController extends Controller
{
    /**
     * @Route("/", name="admin_index")
     */
    public function indexAction(Request $request)
    {
        $user = $this->getUser();
        $configRespository = $this->getDoctrine()->getRepository('SocialWallBundle:SocialMediaConfig');
        $accessTokens = $this->getUser()->getAccessTokens();
        $translator = $this->get('translator');
        if (!isset($accessTokens[SocialMediaType::FACEBOOK])) {
            $facebookHelper = $this->get('facebook_helper');
            $this->addFlash('success', $translator->trans('admin.flash.login', [
                '%url%' => '<a href="'.$facebookHelper->oAuthHandler($this->generateUrl('admin_facebook_login', [], true)).'">Here</a>',
                '%media%' => 'facebook'
            ]));
        }
        if (!isset($accessTokens[SocialMediaType::INSTAGRAM])) {
            $instagramHelper = $this->get('instagram_helper');
            $this->addFlash('success', $translator->trans('admin.flash.login', [
                '%url%' => '<a href="'.$instagramHelper->oAuthHandler($this->generateUrl('admin_instagram_login', [], true), $request).'">Here</a>',
                '%media%' => 'instagram'
            ]));
        }

        return $this->render('::Admin/index.html.twig', [
            'facebookConfigs' => $configRespository->getConfigs([SocialMediaType::FACEBOOK], $user),
            'instagramConfigs' => $configRespository->getConfigs([SocialMediaType::INSTAGRAM], $user),
        ]);
    }
}
