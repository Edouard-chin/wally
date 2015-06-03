<?php

namespace SocialWallBundle\Controller\Front;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

use Netexplo\AcademyBundle\Entity\Log;

class LogController extends Controller
{
	/**
	 * @Route("/log", name="analytics")
	 */
    public function indexAction(Request $request)
    {
        if ($this->getUser()) {
            $logger = $this->get('monolog.logger.analytics');
            $logger->info($request->query->get('path'));
        }

        return new Response();
    }
}
