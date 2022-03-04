<?php

/**
 * EventListener.php - Event listener
 *
 * Allow the use of the class methods as event handlers.
 * See https://github.com/lemonphp/event for more information.
 *
 * @package jaxon-core
 * @author Thierry Feuzeu <thierry.feuzeu@gmail.com>
 * @copyright 2017 Thierry Feuzeu <thierry.feuzeu@gmail.com>
 * @license https://opensource.org/licenses/BSD-3-Clause BSD 3-Clause License
 * @link https://github.com/jaxon-php/jaxon-core
 */

namespace Jaxon\Contracts\Event;

use Lemon\Event\EventSubscriberInterface;

interface Listener extends EventSubscriberInterface
{
    /**
     * Return an array of events to listen to.
     *
     * The array keys are event names and the value is the method name to call.
     * For instance:
     *  ['eventType' => 'methodName']
     *
     * @return array
     */
    public function getEvents(): array;
}
