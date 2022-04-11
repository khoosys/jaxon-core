<?php

namespace Jaxon\Di\Traits;

use Jaxon\Jaxon;
use Jaxon\App\App;
use Jaxon\App\Bootstrap;
use Jaxon\App\Translator;
use Jaxon\Config\ConfigEventManager;
use Jaxon\Config\ConfigManager;
use Jaxon\Di\Container;
use Jaxon\Plugin\Manager\PackageManager;
use Jaxon\Request\Handler\CallbackManager;
use Jaxon\Ui\Dialog\Library\DialogLibraryManager;
use Jaxon\Ui\View\ViewRenderer;
use Jaxon\Utils\Config\ConfigReader;

trait AppTrait
{
    /**
     * @var array The default config options
     */
    protected $aConfig =  [
        'core' => [
            'version'               => Jaxon::VERSION,
            'language'              => 'en',
            'encoding'              => 'utf-8',
            'decode_utf8'           => false,
            'prefix' => [
                'function'          => 'jaxon_',
                'class'             => 'Jaxon',
            ],
            'request' => [
                // 'uri'            => '',
                'mode'              => 'asynchronous',
                'method'            => 'POST', // W3C: Method is case sensitive
            ],
            'response' => [
                'send'              => true,
                'merge.ap'          => true,
                'merge.js'          => true,
            ],
            'debug' => [
                'on'                => false,
                'verbose'           => false,
            ],
            'process' => [
                'exit'              => true,
                'clean'             => false,
                'timeout'           => 6000,
            ],
            'error' => [
                'handle'            => false,
                'log_file'          => '',
            ],
            'jquery' => [
                'no_conflict'       => false,
            ],
            'upload' => [
                'enabled'           => true,
            ],
        ],
        'js' => [
            'lib' => [
                'output_id'         => 0,
                'queue_size'        => 0,
                'load_timeout'      => 2000,
                'show_status'       => false,
                'show_cursor'       => true,
            ],
            'app' => [
                'dir'               => '',
                'options'           => '',
            ],
        ],
    ];

    /**
     * Register the values into the container
     *
     * @return void
     */
    private function registerApp()
    {
        // Translator
        $this->set(Translator::class, function($c) {
            $xTranslator = new Translator();
            $sResourceDir = rtrim(trim($c->g('jaxon.core.dir.translation')), '/\\');
            // Load the Jaxon package translations
            $xTranslator->loadTranslations($sResourceDir . '/en/errors.php', 'en');
            $xTranslator->loadTranslations($sResourceDir . '/fr/errors.php', 'fr');
            $xTranslator->loadTranslations($sResourceDir . '/es/errors.php', 'es');
            // Load the config translations
            $xTranslator->loadTranslations($sResourceDir . '/en/config.php', 'en');
            $xTranslator->loadTranslations($sResourceDir . '/fr/config.php', 'fr');
            $xTranslator->loadTranslations($sResourceDir . '/es/config.php', 'es');
            // Load the upload translations
            $xTranslator->loadTranslations($sResourceDir . '/en/upload.php', 'en');
            $xTranslator->loadTranslations($sResourceDir . '/fr/upload.php', 'fr');
            $xTranslator->loadTranslations($sResourceDir . '/es/upload.php', 'es');
            return $xTranslator;
        });
        // Config Manager
        $this->set(ConfigEventManager::class, function($c) {
            return new ConfigEventManager($c->g(Container::class));
        });
        $this->set(ConfigManager::class, function($c) {
            $xEventManager = $c->g(ConfigEventManager::class);
            $xConfigManager = new ConfigManager($c->g(ConfigReader::class), $xEventManager, $c->g(Translator::class));
            $xConfigManager->setOptions($this->aConfig);
            // It's important to call this after the $xConfigManager->setOptions(),
            // because we don't want to trigger the events since the listeners cannot yet be instantiated.
            $xEventManager->addListener(Translator::class);
            $xEventManager->addListener(DialogLibraryManager::class);
            return $xConfigManager;
        });
        // Jaxon App
        $this->set(App::class, function($c) {
            return new App($c->g(Jaxon::class), $c->g(ConfigManager::class), $c->g(Translator::class));
        });
        // Jaxon App bootstrap
        $this->set(Bootstrap::class, function($c) {
            return new Bootstrap($c->g(ConfigManager::class), $c->g(PackageManager::class),
                $c->g(CallbackManager::class), $c->g(ViewRenderer::class));
        });
    }

    /**
     * Get the App instance
     *
     * @return App
     */
    public function getApp(): App
    {
        return $this->g(App::class);
    }

    /**
     * Get the App bootstrap
     *
     * @return Bootstrap
     */
    public function getBootstrap(): Bootstrap
    {
        return $this->g(Bootstrap::class);
    }
}
