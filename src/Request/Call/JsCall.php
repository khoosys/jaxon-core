<?php

/**
 * JsCall.php - A javascript function call, with its parameters.
 *
 * @package jaxon-core
 * @author Jared White
 * @author J. Max Wilson
 * @author Joseph Woolley
 * @author Steffen Konerow
 * @author Thierry Feuzeu
 * @copyright Copyright (c) 2005-2007 by Jared White & J. Max Wilson
 * @copyright Copyright (c) 2008-2010 by Joseph Woolley, Steffen Konerow, Jared White  & J. Max Wilson
 * @copyright 2016 Thierry Feuzeu <thierry.feuzeu@gmail.com>
 * @license https://opensource.org/licenses/BSD-3-Clause BSD 3-Clause License
 * @link https://github.com/jaxon-php/jaxon-core
 */

namespace Jaxon\Request\Call;

use JsonSerializable;

class JsCall implements JsonSerializable
{
    /**
     * The name of the javascript function
     *
     * @var string
     */
    private $sFunction;

    /**
     * @var string
     */
    public $sQuoteCharacter = '"';

    /**
     * @var array
     */
    protected $aParameters = [];

    /**
     * The constructor.
     *
     * @param string $sFunction    The javascript function
     */
    public function __construct(string $sFunction)
    {
        $this->sFunction = $sFunction;
    }

    /**
     * Instruct the request to use single quotes when generating the javascript
     *
     * @return JsCall
     */
    public function useSingleQuote(): JsCall
    {
        $this->sQuoteCharacter = "'";
        return $this;
    }

    /**
     * Instruct the request to use single quotes when generating the javascript
     *
     * @return JsCall
     */
    public function useSingleQuotes(): JsCall
    {
        $this->sQuoteCharacter = "'";
        return $this;
    }

    /**
     * Instruct the request to use double quotes while generating the javascript
     *
     * @return JsCall
     */
    public function useDoubleQuote(): JsCall
    {
        $this->sQuoteCharacter = '"';
        return $this;
    }

    /**
     * Instruct the request to use double quotes while generating the javascript
     *
     * @return JsCall
     */
    public function useDoubleQuotes(): JsCall
    {
        $this->sQuoteCharacter = '"';
        return $this;
    }

    /**
     * Clear the parameter list associated with this request
     *
     * @return JsCall
     */
    public function clearParameters(): JsCall
    {
        $this->aParameters = [];
        return $this;
    }

    /**
     * Set the value of the parameter at the given position
     *
     * @param Contracts\Parameter $xParameter    The value to be used
     *
     * @return JsCall
     */
    public function pushParameter(Contracts\Parameter $xParameter): JsCall
    {
        $this->aParameters[] = $xParameter;
        return $this;
    }

    /**
     * Add a parameter value to the parameter list for this request
     *
     * @param string $sType    The type of the value to be used
     * @param string $sValue    The value to be used
     *
     * Types should be one of the following <Parameter::FORM_VALUES>, <Parameter::QUOTED_VALUE>, <Parameter::NUMERIC_VALUE>,
     * <Parameter::JS_VALUE>, <Parameter::INPUT_VALUE>, <Parameter::CHECKED_VALUE>, <Parameter::PAGE_NUMBER>.
     * The value should be as follows:
     * - <Parameter::FORM_VALUES> - Use the ID of the form you want to process.
     * - <Parameter::QUOTED_VALUE> - The string data to be passed.
     * - <Parameter::JS_VALUE> - A string containing valid javascript
     *   (either a javascript variable name that will be in scope at the time of the call or
     *   a javascript function call whose return value will become the parameter).
     *
     * @return JsCall
     */
    public function addParameter(string $sType, string $sValue): JsCall
    {
        $this->pushParameter(new Parameter($sType, $sValue));
        return $this;
    }

    /**
     * Add a set of parameters to this request
     *
     * @param array $aParameters    The parameters
     *
     * @return JsCall
     */
    public function addParameters(array $aParameters): JsCall
    {
        foreach($aParameters as $xParameter)
        {
            if($xParameter instanceof JsCall)
            {
                $this->addParameter(Parameter::JS_VALUE, 'function(){' . $xParameter->getScript() . ';}');
            }
            else
            {
                $this->pushParameter(Parameter::make($xParameter));
            }
        }
        return $this;
    }

    /**
     * Returns a string representation of the script output (javascript) from this request object
     *
     * @return string
     */
    public function getScript(): string
    {
        return $this->sFunction . '(' . implode(', ', $this->aParameters) . ')';
    }

    /**
     * Convert this request object to string
     *
     * @return string
     */
    public function __toString()
    {
        return $this->getScript();
    }

    /**
     * Convert this request object to string, when converting the response into json.
     *
     * This is a method of the JsonSerializable interface.
     *
     * @return string
     */
    public function jsonSerialize(): string
    {
        return $this->getScript();
    }
}
