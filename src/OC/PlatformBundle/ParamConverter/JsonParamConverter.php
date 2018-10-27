<?php

namespace OC\PlatformBundle\ParamConverter;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter\ParamConverterInterface;
use Symfony\Component\HttpFoundation\Request;

class JsonParamConverter implements ParamConverterInterface
{
    function supports(ParamConverter $configuration)
    {
        return 'json' === $configuration->getName();;
    }

    function apply(Request $request, ParamConverter $configuration)
    {
        $json = $request->attributes->get('json');
        $json = json_decode($json);
        $request->attributes->set('json', $json);
    }
}