<?php

namespace Jaxon\Container\Traits;

use Jaxon\Utils\Config\Config;
use Jaxon\Utils\Config\Reader;

trait ConfigTrait
{
    /**
     * Register the values into the container
     *
     * @return void
     */
    private function registerConfigs()
    {
        $this->set(Reader::class, function($c) {
            return new Reader($c->g(Config::class));
        });
        $this->set(Config::class, function($c) {
            return new Config($c->g('jaxon.core.options'));
        });
    }

    /**
     * Get the config reader
     *
     * @return Reader
     */
    public function getConfigReader()
    {
        return $this->g(Reader::class);
    }

    /**
     * Get the library config options
     *
     * @return Config
     */
    public function getConfig()
    {
        return $this->g(Config::class);
    }

    /**
     * Create a new the config manager
     *
     * @param array             $aOptions           The options array
     * @param string            $sKeys              The key prefix of the config options
     *
     * @return Config
     */
    public function newConfig(array $aOptions = [], string $sKeys = '')
    {
        return new Config($aOptions, $sKeys);
    }
}