<?php

namespace AppBundle\Controller;

use AppBundle\Form\Type\RegistrationType;
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

            if ($form->get('conditionsAccepted')->getData() !== true) {
                $this->addFlash(
                    'error',
                    'Bitte stimmen Sie den Teilnahmebedingungen zu.'
                );
                return $this->render(
                    'AppBundle:default:index.html.twig',
                    ['form' => $form->createView()],
                    new Response(null, 422)
                );
            }

            $customer = $form->getData();

            $em = $this->getDoctrine()->getManager();
            $repo = $em->getRepository('AppBundle\Entity\Customer');

            $existingCustomer = $repo->findOneBy(['email' => $customer->getEmail()]);
            if (!empty($existingCustomer)) {
                $this->addFlash(
                    'error',
                    'Diese E-Mail Adresse kann nicht erneut für eine Registrierung verwendet werden.'
                );
                return $this->render(
                    'AppBundle:default:index.html.twig',
                    ['form' => $form->createView()],
                    new Response(null, 403)
                );
            }

            $existingCustomer = $repo->findOneBy([
                'salesdivision' => $customer->getSalesDivision(),
                'employeeNumber' => $customer->getEmployeeNumber()
            ]);
            if (!empty($existingCustomer)) {
                $this->addFlash(
                    'error',
                    'Diese Mitarbeiternummer kann nicht erneut für eine Registrierung verwendet werden.'
                );
                return $this->render(
                    'AppBundle:default:index.html.twig',
                    ['form' => $form->createView()],
                    new Response(null, 403)
                );
            }

            $customer->setCouponsHaveBeenSent(false);
            $customer->setIsActivated(false);
            $secret = $this->getParameter('secret');
            $customer->setActivationCode(sha1($secret . $customer->getEmail()));

            $em = $this->getDoctrine()->getManager();
            $em->persist($customer);
            $em->flush();

            $message = \Swift_Message::newInstance()
                ->setSubject('Ihre Registrierung für die Goodbye Kaufhof Sonderaktion')
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

            $showCustomerdata = false;
            if (   $this->get('kernel')->getEnvironment() === 'dev'
                || $this->get('kernel')->getEnvironment() === 'preprod') {
                $showCustomerdata = true;
            }

            $this->addFlash('success', 'Vielen Dank. Sie erhalten nun eine Aktivierungsmail.');
            return $this->render(
                'AppBundle:default:thankyou.html.twig',
                [
                    'showCustomerdata' => $showCustomerdata,
                    'customer' => $customer
                ]
            );
        }

        if ($form->isSubmitted() && !$form->isValid()) {
            $this->addFlash(
                'error',
                'Das Formular ist nicht korrekt ausgefüllt.'
            );
            return $this->render(
                'AppBundle:default:index.html.twig',
                ['form' => $form->createView()],
                new Response(null, 422)
            );
        }

        $response = new Response();
        if (!$form->isSubmitted()) {
            $response->setPublic();
            $response->setSharedMaxAge('60');
        }

        return $this->render(
            'AppBundle:default:index.html.twig',
            [
                'form' => $form->createView()
            ],
            $response
        );
    }

    /**
     * @Route("/customer/{customerId}/confirmation/{activationCode}", requirements={"customerId" = "\d+"}, name="confirm")
     * @Method({"GET"})
     */
    public function confirmAction(Request $request, $customerId, $activationCode)
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

        if ($customer->getIsActivated() === true) {
            $this->addFlash(
                'error',
                'Eine erneute Freischaltung ist nicht möglich!'
            );
            return $this->render(
                'AppBundle:default:confirm.html.twig',
                [],
                new Response(null, 410)
            );
        }

        $customer->setIsActivated(true, $request->headers->get('X_FORWARDED_FOR'));
        $em->flush();


        $this->addFlash(
            'success',
            'Vielen Dank, Ihre Freischaltung war erfolgreich. Sie erhalten in wenigen Minuten eine E-Mail mit Ihren persönlichen Rabattcodes.'
        );

        return $this->render(
            'AppBundle:default:confirm.html.twig'
        );
    }
}
