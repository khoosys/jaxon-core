<?php

namespace Jaxon\Container\Traits;

use Jaxon\Jaxon;
use Jaxon\App\App;
use Jaxon\App\Bootstrap;
use Jaxon\Plugin\Manager as PluginManager;
use Jaxon\Request\Handler\Handler as RequestHandler;
use Jaxon\Response\Manager as ResponseManager;
use Jaxon\Ui\View\Manager as ViewManager;
use Jaxon\Utils\Config\Reader as ConfigReader;

trait AppTrait
{
    /**
     * Register the values into the container
     *
     * @return void
     */
    private function registerApp()
    {
        // Jaxon App
        $this->set(App::class, function($c) {
            return new App($c->g(Jaxon::class), $c->g(ResponseManager::class), $c->g(ConfigReader::class));
        });
        // Jaxon App bootstrap
        $this->set(Bootstrap::class, function($c) {
            return new Bootstrap($c->g(Jaxon::class), $c->g(PluginManager::class),
                $c->g(ViewManager::class), $c->g(RequestHandler::class));
        });
    }

    /**
     * Get the App instance
     *
     * @return App
     */
    public function getApp()
    {
        return $this->g(App::class);
    }

    /**
     * Get the App bootstrap
     *
     * @return Bootstrap
     */
    public function getBootstrap()
    {
        return $this->g(Bootstrap::class);
    }
}
