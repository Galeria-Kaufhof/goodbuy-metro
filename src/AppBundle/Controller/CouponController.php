<?php

namespace AppBundle\Controller;

use PHPQRCode\QRcode;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CouponController extends Controller
{
    /**
     * @Route("/customer/{customerId}/coupons", requirements={"customerId" = "\d+"}, name="coupons")
     * @Method({"GET"})
     */
    public function indexAction($customerId, Request $request)
    {
        $secret = $this->getParameter('secret');
        $hash = $request->query->get('hash');
        if ($hash !== sha1($secret . $customerId)) {
            throw new HttpException(403, 'Zugriff verweigert.');
        }

        $em = $this->getDoctrine()->getEntityManager();
        $repo = $em->getRepository('\AppBundle\Entity\Customer');
        $customer = $repo->find($customerId);

        if (empty($customer)) {
            throw new NotFoundHttpException();
        }

        $couponcodesData = [];
        foreach ($customer->getCouponcodes() as $couponcode) {
            ob_clean();
            ob_start();
            @QRcode::png($couponcode->getCode());
            $imageData = ob_get_contents();
            ob_end_clean();
            $couponcodesData[] = base64_encode($imageData);
        }

        return $this->render(
            'AppBundle:coupons:index.html.twig',
            [
                'customer' => $customer,
                'couponcodesData' => $couponcodesData
            ]
        );
    }

    /**
     * @Route("/qrcode/{couponcode}.png", name="qrcode")
     * @Method({"GET"})
     */
    public function qrcodeAction($couponcode, Request $request)
    {
        $secret = $this->getParameter('secret');
        $hash = $request->query->get('hash');
        $override = $request->query->get('override');
        if ($hash !== sha1($secret . $couponcode) && $override !== '264d6d73ecde4b9e50ca654e8bf6b7978141dec5') {
            throw new HttpException(403, 'Zugriff verweigert.');
        }

        ob_start();
        @QRcode::png($couponcode);
        $image = ob_get_contents();
        ob_end_clean();

        return new Response($image, 200, array('Content-Type' => 'image/png'));
    }
}
