<?php

use Jaxon\Response\Response;

class ClassA
{
    public function methodAa(): Response
    {
        $xResponse = jaxon()->getResponse();
        $xResponse->html('div', 'This is the div content!!');
        return $xResponse;
    }

    public function methodAb(): Response
    {
        $xResponse = jaxon()->getResponse();
        $xResponse->html('div', 'This is the div content!!');
        return $xResponse;
    }
}
