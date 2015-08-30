<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class CouponController extends Controller
{
    /**
     * @Route("/customer/{customerId}/coupons", requirements={"id" = "\d+"}, name="coupons")
     * @Method({"GET"})
     */
    public function indexAction($customerId, Request $request)
    {
        $secret = $this->getParameter('secret');
        $hash = $request->query->get('hash');
        if ($hash !== sha1($secret . $customerId)) {
            throw $this->createAccessDeniedException('Zugriff abgelehnt.');
        }

        return $this->render(
            'AppBundle:coupons:index.html.twig',
            ['customerId' => $customerId]
        );
    }
}
