<?php

namespace OC\CoreBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class CoreController extends Controller
{

    public function indexAction()
    {
        return $this->render('@OCCore/Core/index.html.twig');
    }

    public function contactAction()
    {
        $this->addFlash('notice', "La page de contact n'est pas encore disponible. merci de revenir plutard");
        return $this->redirectToRoute('core_homepage');
    }
}