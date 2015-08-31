<?php

namespace AppBundle\Controller;

use AppBundle\Form\Type\RegistrationType;
use PHPQRCode\QRcode;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class DefaultController extends Controller
{
    /**
     * @Route("/", name="default")
     * @Method({"GET", "POST"})
     */
    public function indexAction(Request $request)
    {
        $form = $this->createForm(new RegistrationType());
        $form->handleRequest($request);

        if ($form->isValid()) {
            $this->addFlash('success', 'Vielen Dank. Sie erhalten nun eine Aktivierungsmail.');

            $customer = $form->getData();
            $customer->setIsActivated(false);
            $secret = $this->getParameter('secret');
            $customer->setActivationCode(sha1($secret . $customer->getEmail()));

            $em = $this->getDoctrine()->getManager();
            $em->persist($customer);
            $em->flush();

            $message = \Swift_Message::newInstance()
                ->setSubject('Ihre Registrierung für die Good Bye Metro Sonderaktion')
                ->setFrom('goodbye-metro@kaufhof.de')
                ->setTo($customer->getEmail())
                ->setBody(
                    $this->renderView(
                        'Emails/activateRegistration.html.twig',
                        [
                            'customer' => $customer
                        ]
                    ),
                    'text/html'
                )
            ;
            $this->get('mailer')->send($message);

            return $this->render(
                'AppBundle:default:thankyou.html.twig'
            );
        }

        return $this->render(
            'AppBundle:default:index.html.twig',
            [
                'form' => $form->createView()
            ]
        );
    }

    /**
     * @Route("/customer/{customerId}/confirmation/{activationCode}", requirements={"customerId" = "\d+"}, name="confirm")
     * @Method({"GET"})
     */
    public function confirmAction($customerId, $activationCode)
    {
        $em = $this->getDoctrine()->getManager();
        $repo = $em->getRepository('AppBundle\Entity\Customer');

        $customer = $repo->find($customerId);

        if (empty($customer) || $customer->getActivationCode() !== $activationCode) {
            $this->addFlash(
                'error',
                'Dieser Aktivierungslink ist leider ungültig.'
            );
            return $this->render(
                'AppBundle:default:confirm.html.twig',
                [],
                new Response(null, 403)
            );
        }

        $customer->setIsActivated(true);
        $em->flush();

        $this->get('couponmapper')->mapNToCustomer(6, $customer);
        //@TODO Handle error case

        $couponcodesData = [];
        foreach ($customer->getCouponcodes() as $couponcode) {
            ob_clean();
            ob_start();
            @QRcode::png($couponcode->getCode());
            $imageData = ob_get_contents();
            ob_end_clean();
            $couponcodesData[] = base64_encode($imageData);
        }

        $pdfData = $this->get('knp_snappy.pdf')->getOutputFromHtml(
            $this->renderView(
                'AppBundle:coupons:index.html.twig',
                array(
                    'customer' => $customer,
                    'couponcodesData' => $couponcodesData
                )
            )
        );

        $message = \Swift_Message::newInstance()
            ->setSubject('Ihre Rabattcodes für die Good Bye Metro Sonderaktion')
            ->setFrom('goodbye-metro@kaufhof.de')
            ->setTo($customer->getEmail())
            ->setBody(
                $this->renderView(
                    'Emails/couponCodes.html.twig',
                    [
                        'customer' => $customer
                    ]
                ),
                'text/html'
            )
            ->attach(\Swift_Attachment::newInstance($pdfData, 'Goodbye-Metro-Rabattcodes.pdf', 'application/pdf'));

        $this->get('mailer')->send($message);

        $this->addFlash(
            'success',
            'Vielen Dank, Ihre Freischaltung war erfolgreich. Sie erhalten nun eine E-Mail mit Ihren persönlichen Rabattcodes.'
        );

        return $this->render(
            'AppBundle:default:confirm.html.twig'
        );
    }
}
