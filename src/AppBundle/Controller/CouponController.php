<?php

namespace AppBundle\Controller;

use PHPQRCode\QRcode;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

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

    /**
     * @Route("/qrcode/{couponcode}.png", requirements={"couponcode" = "\d+"}, name="qrcode")
     * @Method({"GET"})
     */
    public function qrcodeAction($couponcode, Request $request)
    {
        $secret = $this->getParameter('secret');
        $hash = $request->query->get('hash');
        if ($hash !== sha1($secret . $couponcode)) {
            throw $this->createAccessDeniedException('Zugriff abgelehnt.');
        }

        ob_start();
        QRcode::png($couponcode);
        $image = ob_get_contents();
        ob_clean();

        return new Response($image, 200, array('Content-Type' => 'image/png'));
    }
}
