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
        }

        return $this->render(
            'AppBundle:default:index.html.twig',
            [
                'form' => $form->createView()
            ]
        );
    }
}
