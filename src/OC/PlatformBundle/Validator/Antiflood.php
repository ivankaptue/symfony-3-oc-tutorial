<?php

namespace OC\PlatformBundle\Validator;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class Antiflood
{
    public $message = "Votre message %string% est considéré comme flood";

    public function validatedBy()
    {
//        return 'oc_platform_antiflood';
    }
}