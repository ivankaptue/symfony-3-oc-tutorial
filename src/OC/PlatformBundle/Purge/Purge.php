<?php

namespace OC\PlatformBundle\Purge;

use Doctrine\ORM\EntityManager;

class Purge
{

    private $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function purge($days)
    {
        $advertRepository = $this->em->getRepository('OCPlatformBundle:Advert');
        $adverts = $advertRepository->getLastAdvertsByDays($days);
        foreach ($adverts as $advert) {
            $this->em->remove($advert);
        }
        $this->em->flush();
    }

}