<?php

namespace OC\PlatformBundle\Controller;

use OC\PlatformBundle\Entity\Advert;
use OC\PlatformBundle\Event\MessagePostEvent;
use OC\PlatformBundle\Event\PlatformEvents;
use OC\PlatformBundle\Form\AdvertEditType;
use OC\PlatformBundle\Form\AdvertType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class AdvertController extends Controller
{

    /**
     * @param $json php array from json object
     * @return Response
     *
     * @ParamConverter(name = "json")
     */
    public function paramConverterAction($json)
    {
        return new Response(print_r($json, true));
    }

    public function purgeAction($days)
    {
        /* $purgeService = $this->get('oc_platform.purger.advert');
         $purgeService->purge($days);
         return new Response('Annonces purgées');*/

//        $em = $this->getDoctrine()->getManager();
//        LoadUser::load($em);

        $advert = new Advert;

        $advert->setDate(new \Datetime());  // Champ « date » OK
        $advert->setTitle(null);           // Champ « title » incorrect : moins de 10 caractères
        //$advert->setContent('blabla');    // Champ « content » incorrect : on ne le définit pas
        $advert->setAuthor('A');            // Champ « author » incorrect : moins de 2 caractères

        // On récupère le service validator
        $validator = $this->get('validator');

        // On déclenche la validation sur notre object
        $listErrors = $validator->validate($advert);

        // Si $listErrors n'est pas vide, on affiche les erreurs
        if (count($listErrors) > 0) {
            // $listErrors est un objet, sa méthode __toString permet de lister joliement les erreurs
            return new Response((string)$listErrors);
        } else {
            return new Response("L'annonce est valide !");
        }
    }

    public function indexAction($page, Request $request)
    {
        if ($page < 1) {
            throw new NotFoundHttpException("la page \"$page\" n'existe pas");
        }

        $nbPerPage = 3;

        $em = $this->getDoctrine()->getManager();
        $advertRepository = $em->getRepository("OCPlatformBundle:Advert");
        $listAdverts = $advertRepository->getAdverts($page, $nbPerPage);

        // On calcule le nombre total de pages grâce au count($listAdverts) qui retourne le nombre total d'annonces
        $nbPages = ceil(count($listAdverts) / $nbPerPage);

        // Si la page n'existe pas, on retourne une 404
        if ($page > $nbPages) {
            throw $this->createNotFoundException("La page " . $page . " n'existe pas.");
        }

        return $this->render("@OCPlatform/Advert/index.html.twig", [
            'listAdverts' => $listAdverts,
            'total' => $nbPages,
            'current_page' => $page
        ]);
    }

    /**
     * @param Advert $advert
     * @return Response
     * @ParamConverter(name="advert", options={ "mapping" : {"advert_id" : "id"} })
     */
    public function viewAction(Advert $advert)
    {
        $em = $this->getDoctrine()->getManager();

        // Pour récupérer une seule annonce, on utilise la méthode find($id)
//        $advert = $em->getRepository('OCPlatformBundle:Advert')->find($id);

        // $advert est donc une instance de OC\PlatformBundle\Entity\Advert
        // ou null si l'id $id n'existe pas, d'où ce if :
//        if (null === $advert) {
//            throw new NotFoundHttpException("L'annonce d'id " . $id . " n'existe pas.");
//        }

        // Récupération de la liste des candidatures de l'annonce
        $listApplications = $em
            ->getRepository('OCPlatformBundle:Application')
            ->findBy(array('advert' => $advert));

        // Récupération des AdvertSkill de l'annonce
        $listAdvertSkills = $em
            ->getRepository('OCPlatformBundle:AdvertSkill')
            ->findBy(array('advert' => $advert));

        return $this->render('OCPlatformBundle:Advert:view.html.twig', array(
            'advert' => $advert,
            'listApplications' => $listApplications,
            'listAdvertSkills' => $listAdvertSkills,
        ));
    }


    /**
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|Response
     * @Security("has_role('ROLE_AUTEUR')")
     */
    public function addAction(Request $request)
    {
        // On vérifie que l'utilisateur dispose bien du rôle ROLE_AUTEUR
        /*if (!$this->get('security.authorization_checker')->isGranted('ROLE_AUTEUR')) {
            // Sinon on déclenche une exception « Accès interdit »
            throw new AccessDeniedException('Accès limité aux auteurs.');
        }*/

        $advert = new Advert();

        $form = $this->createForm(AdvertType::class, $advert);

        if ($request->isMethod('POST') && $form->handleRequest($request)->isValid()) {
            $advert->setUser($this->getUser());

            $event = new MessagePostEvent($advert->getContent(), $advert->getUser());
            $this->get('event_dispatcher')->dispatch(PlatformEvents::POST_MESSAGE, $event);

            $advert->setContent($event->getMessage());

            $em = $this->getDoctrine()->getManager();
            $em->persist($advert);
            $em->flush();

            $this->addFlash('notice', "Annonce bien enregistrée !");
            return $this->redirectToRoute('oc_platform_view', ['id' => $advert->getId()]);
        }

        return $this->render('@OCPlatform/Advert/add.html.twig', [
            'form' => $form->createView()
        ]);
    }

    public function editAction($id, Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $advertRepository = $em->getRepository("OCPlatformBundle:Advert");
        $advert = $advertRepository->find($id);

        if ($advert == null) {
            throw new NotFoundHttpException("L'annonce d'id $id n'existe pas");
        }

        $form = $this->createForm(AdvertEditType::class, $advert);

        if ($request->isMethod('POST')) {
            $form->handleRequest($request);
            if ($form->isValid()) {
                $em = $this->getDoctrine()->getManager();
                $em->merge($advert);
                $em->flush();
                $this->addFlash('notice', "Annonce bien modifiée !");
                return $this->redirectToRoute('oc_platform_view', ['id' => $advert->getId()]);
            }
        }

        return $this->render("@OCPlatform/Advert/edit.html.twig", [
            'advert' => $advert,
            'form' => $form->createView()
        ]);
    }

    public function deleteAction($id, Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        $advert = $em->getRepository('OCPlatformBundle:Advert')->find($id);

        if (null === $advert) {
            throw new NotFoundHttpException("L'annonce d'id " . $id . " n'existe pas.");
        }

        // On crée un formulaire vide, qui ne contiendra que le champ CSRF
        // Cela permet de protéger la suppression d'annonce contre cette faille
        $form = $this->get('form.factory')->create();

        if ($request->isMethod('POST') && $form->handleRequest($request)->isValid()) {
            $em->remove($advert);
            $em->flush();

            $request->getSession()->getFlashBag()->add('info', "L'annonce a bien été supprimée.");

            return $this->redirectToRoute('oc_platform_home');
        }

        return $this->render('OCPlatformBundle:Advert:delete.html.twig', array(
            'advert' => $advert,
            'form' => $form->createView(),
        ));
    }

    public function menuAction($limit)
    {
        $advertRepository = $this->getDoctrine()->getManager()->getRepository("OCPlatformBundle:Advert");
        $listAdverts = $advertRepository->findLastAdverts($limit);

        return $this->render("@OCPlatform/Advert/menu.html.twig", array(
            'listAdverts' => $listAdverts
        ));
    }

    public function translationAction($name)
    {
        return $this->render('OCPlatformBundle:Advert:translation.html.twig', array(
            'name' => $name
        ));
    }
}
