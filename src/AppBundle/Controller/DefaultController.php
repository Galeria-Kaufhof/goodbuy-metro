<?php

namespace AppBundle\Controller;

use AppBundle\Form\Type\RegistrationType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

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
                        array('activationCode' => $customer->getActivationCode())
                    ),
                    'text/html'
                )
                /*
                 * If you also want to include a plaintext version of the message
                ->addPart(
                    $this->renderView(
                        'Emails/registration.txt.twig',
                        array('name' => $name)
                    ),
                    'text/plain'
                )
                */
            ;
            $this->get('mailer')->send($message);

        }

        return $this->render(
            'AppBundle:default:index.html.twig',
            [
                'form' => $form->createView()
            ]
        );
    }
}
