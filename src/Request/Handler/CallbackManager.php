<?php

/**
 * Callbacks.php - Jaxon request callbacks
 *
 * @package jaxon-core
 * @author Thierry Feuzeu <thierry.feuzeu@gmail.com>
 * @copyright 2017 Thierry Feuzeu <thierry.feuzeu@gmail.com>
 * @license https://opensource.org/licenses/BSD-3-Clause BSD 3-Clause License
 * @link https://github.com/jaxon-php/jaxon-core
 */

namespace Jaxon\Request\Handler;

use function count;

class CallbackManager
{
    /**
     * True if a new bon boot callback was added.
     *
     * @var bool
     */
    protected $nBootCallbackAdded = false;

    /**
     * Number of on boot callbacks already called.
     *
     * @var int
     */
    protected $nBootCallCount = 0;

    /**
     * The callbacks to run after booting the library
     *
     * @var callable[]
     */
    protected $aBootCallbacks = [];

    /**
     * The callbacks to run before processing the request
     *
     * @var callable[]
     */
    protected $aBeforeCallbacks = [];

    /**
     * The callbacks to run afteer processing the request
     *
     * @var callable[]
     */
    protected $aAfterCallbacks = [];

    /**
     * The callbacks to run in case of invalid request
     *
     * @var callable[]
     */
    protected $aInvalidCallbacks = [];

    /**
     * The callbacks to run in case of error
     *
     * @var callable[]
     */
    protected $aErrorCallbacks = [];

    /**
     * The callbacks to run when a class is instanced
     *
     * @var callable[]
     */
    protected $aInitCallbacks = [];

    /**
     * @return bool
     */
    public function bootCallbackAdded(): bool
    {
        return $this->nBootCallbackAdded;
    }

    /**
     * Get the number of on boot callbacks already called
     *
     * @return int
     */
    public function getBootCallCount(): int
    {
        return $this->nBootCallCount;
    }

    public function updateBootCalls()
    {
        $this->nBootCallbackAdded = false;
        $this->nBootCallCount = count($this->aBootCallbacks);
    }

    /**
     * Get the library booting callbacks.
     *
     * @return callable[]
     */
    public function getBootCallbacks(): array
    {
        return $this->aBootCallbacks;
    }

    /**
     * Get the pre-request processing callbacks.
     *
     * @return callable[]
     */
    public function getBeforeCallbacks(): array
    {
        return $this->aBeforeCallbacks;
    }

    /**
     * Get the post-request processing callbacks.
     *
     * @return callable[]
     */
    public function getAfterCallbacks(): array
    {
        return $this->aAfterCallbacks;
    }

    /**
     * Get the invalid request callbacks.
     *
     * @return callable[]
     */
    public function getInvalidCallbacks(): array
    {
        return $this->aInvalidCallbacks;
    }

    /**
     * Get the processing error callbacks.
     *
     * @return callable[]
     */
    public function getErrorCallbacks(): array
    {
        return $this->aErrorCallbacks;
    }

    /**
     * Get the class initialisation callbacks.
     *
     * @return callable[]
     */
    public function getInitCallbacks(): array
    {
        return $this->aInitCallbacks;
    }

    /**
     * Add a library booting callback.
     *
     * @param callable $xCallable    The callback function
     *
     * @return CallbackManager
     */
    public function boot(callable $xCallable): CallbackManager
    {
        $this->aBootCallbacks[] = $xCallable;
        $this->nBootCallbackAdded = true;
        return $this;
    }

    /**
     * Add a pre-request processing callback.
     *
     * @param callable $xCallable    The callback function
     *
     * @return CallbackManager
     */
    public function before(callable $xCallable): CallbackManager
    {
        $this->aBeforeCallbacks[] = $xCallable;
        return $this;
    }

    /**
     * Add a post-request processing callback.
     *
     * @param callable $xCallable    The callback function
     *
     * @return CallbackManager
     */
    public function after(callable $xCallable): CallbackManager
    {
        $this->aAfterCallbacks[] = $xCallable;
        return $this;
    }

    /**
     * Add a invalid request callback.
     *
     * @param callable $xCallable    The callback function
     *
     * @return CallbackManager
     */
    public function invalid(callable $xCallable): CallbackManager
    {
        $this->aInvalidCallbacks[] = $xCallable;
        return $this;
    }

    /**
     * Add a processing error callback.
     *
     * @param callable $xCallable    The callback function
     *
     * @return CallbackManager
     */
    public function error(callable $xCallable): CallbackManager
    {
        $this->aErrorCallbacks[] = $xCallable;
        return $this;
    }

    /**
     * Add a class initialisation callback.
     *
     * @param callable $xCallable    The callback function
     *
     * @return CallbackManager
     */
    public function init(callable $xCallable): CallbackManager
    {
        $this->aInitCallbacks[] = $xCallable;
        return $this;
    }
}
