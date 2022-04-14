<?php

/**
 * Ajax.php
 *
 * The Jaxon class uses a modular plug-in system to facilitate the processing
 * of special Ajax requests made by a PHP page.
 * It generates Javascript that the page must include in order to make requests.
 * It handles the output of response commands (see <Jaxon\Response\Response>).
 * Many flags and settings can be adjusted to effect the behavior of the Jaxon class
 * as well as the client-side javascript.
 *
 * @package jaxon-core
 * @author Jared White
 * @author J. Max Wilson
 * @author Joseph Woolley
 * @author Steffen Konerow
 * @author Thierry Feuzeu <thierry.feuzeu@gmail.com>
 * @copyright Copyright (c) 2005-2007 by Jared White & J. Max Wilson
 * @copyright Copyright (c) 2008-2010 by Joseph Woolley, Steffen Konerow, Jared White  & J. Max Wilson
 * @copyright 2016 Thierry Feuzeu <thierry.feuzeu@gmail.com>
 * @license https://opensource.org/licenses/BSD-3-Clause BSD 3-Clause License
 * @link https://github.com/jaxon-php/jaxon-core
 */

namespace Jaxon\App;

use Jaxon\Jaxon;
use Jaxon\App\Config\ConfigManager;
use Jaxon\App\Dialog\Library\DialogLibraryManager;
use Jaxon\App\I18n\Translator;
use Jaxon\App\Session\SessionInterface;
use Jaxon\App\View\ViewRenderer;
use Jaxon\Di\Container;
use Jaxon\Exception\RequestException;
use Jaxon\Exception\SetupException;
use Jaxon\Plugin\Manager\PluginManager;
use Jaxon\Plugin\Package;
use Jaxon\Plugin\ResponsePlugin;
use Jaxon\Request\Call\Paginator;
use Jaxon\Request\Factory\Factory;
use Jaxon\Request\Factory\Psr\PsrFactory;
use Jaxon\Request\Factory\RequestFactory;
use Jaxon\Request\Handler\CallbackManager;
use Jaxon\Request\Handler\UploadHandler;
use Jaxon\Response\Manager\ResponseManager;
use Jaxon\Response\Response;
use Jaxon\Response\ResponseInterface;
use Jaxon\Utils\Http\UriException;
use Jaxon\Utils\Template\TemplateEngine;
use Psr\Log\LoggerInterface;

use function gmdate;
use function header;
use function headers_sent;
use function trim;

class Ajax
{
    /**
     * @var Ajax
     */
    private static $xInstance = null;

    /**
     * @var Container
     */
    private static $xContainer = null;

    /**
     * @var Bootstrap
     */
    protected $xBootstrap;

    /**
     * @var Translator
     */
    protected $xTranslator;

    /**
     * @var ConfigManager
     */
    protected $xConfigManager;

    /**
     * @var PluginManager
     */
    protected $xPluginManager;

    /**
     * @var ResponseManager
     */
    protected $xResponseManager;

    /**
     * @return void
     */
    private static function initInstance()
    {
        // Set the attributes from the container
        self::$xInstance->xBootstrap = self::$xContainer->g(Bootstrap::class);
        self::$xInstance->xTranslator = self::$xContainer->g(Translator::class);
        self::$xInstance->xConfigManager = self::$xContainer->g(ConfigManager::class);
        self::$xInstance->xPluginManager = self::$xContainer->g(PluginManager::class);
        self::$xInstance->xResponseManager = self::$xContainer->g(ResponseManager::class);
    }

    /**
     * @return Ajax
     */
    public static function getInstance(): ?Ajax
    {
        if(self::$xInstance === null)
        {
            self::$xInstance = new Ajax();
            self::$xContainer = new Container(self::$xInstance);
            self::initInstance();
        }
        // Call the on boot callbacks on each call to the jaxon() function.
        self::$xInstance->xBootstrap->onBoot();
        return self::$xInstance;
    }

    /**
     * The constructor
     */
    private function __construct()
    {}

    /**
     * @return string
     */
    public function getVersion(): string
    {
        return Jaxon::VERSION;
    }

    /**
     * @return Container
     */
    public function di(): ?Container
    {
        return self::$xContainer;
    }

    /**
     * @return LoggerInterface
     */
    public function logger(): LoggerInterface
    {
        return $this->di()->getLogger();
    }

    /**
     * Set the value of a config option
     *
     * @param string $sName    The option name
     * @param mixed $sValue    The option value
     *
     * @return void
     */
    public function setOption(string $sName, $sValue)
    {
        $this->xConfigManager->setOption($sName, $sValue);
    }

    /**
     * Get the value of a config option
     *
     * @param string $sName    The option name
     * @param mixed|null $xDefault    The default value, to be returned if the option is not defined
     *
     * @return mixed
     */
    public function getOption(string $sName, $xDefault = null)
    {
        return $this->xConfigManager->getOption($sName, $xDefault);
    }

    /**
     * Check the presence of a config option
     *
     * @param string $sName    The option name
     *
     * @return bool
     */
    public function hasOption(string $sName): bool
    {
        return $this->xConfigManager->hasOption($sName);
    }

    /**
     * Read the options from the file, if provided, and return the config
     *
     * @param string $sConfigFile The full path to the config file
     * @param string $sConfigSection The section of the config file to be loaded
     *
     * @return ConfigManager
     * @throws SetupException
     */
    public function config(string $sConfigFile = '', string $sConfigSection = ''): ConfigManager
    {
        if(!empty(($sConfigFile = trim($sConfigFile))))
        {
            $this->xConfigManager->load($sConfigFile, trim($sConfigSection));
        }
        return $this->xConfigManager;
    }

    /**
     * @return Translator
     */
    public function translator(): Translator
    {
        return $this->xTranslator;
    }

    /**
     * Get the global Response object
     *
     * @return ResponseInterface
     */
    public function getResponse(): ResponseInterface
    {
        return $this->xResponseManager->getResponse();
    }

    /**
     * Create a new Jaxon response object
     *
     * @return Response
     */
    public function newResponse(): Response
    {
        return $this->di()->newResponse();
    }

    /**
     * Register a plugin
     *
     * Below is a table for priorities and their description:
     * - 0 to 999: Plugins that are part of or extensions to the jaxon core
     * - 1000 to 8999: User created plugins, typically, these plugins don't care about order
     * - 9000 to 9999: Plugins that generally need to be last or near the end of the plugin list
     *
     * @param string $sClassName    The plugin class
     * @param string $sPluginName    The plugin name
     * @param integer $nPriority    The plugin priority, used to order the plugins
     *
     * @return void
     * @throws SetupException
     */
    public function registerPlugin(string $sClassName, string $sPluginName, int $nPriority = 1000)
    {
        $this->xPluginManager->registerPlugin($sClassName, $sPluginName, $nPriority);
    }

    /**
     * Register a package
     *
     * @param string $sClassName    The package class
     * @param array $xPkgOptions    The user provided package options
     *
     * @return void
     * @throws SetupException
     */
    public function registerPackage(string $sClassName, array $xPkgOptions = [])
    {
        $this->di()->getPackageManager()->registerPackage($sClassName, $xPkgOptions);
    }

    /**
     * Register request handlers, including functions, callable classes and directories.
     *
     * @param string $sType    The type of request handler being registered
     *        Options include:
     *        - Jaxon::CALLABLE_FUNCTION: a function declared at global scope
     *        - Jaxon::CALLABLE_CLASS: a class who's methods are to be registered
     *        - Jaxon::CALLABLE_DIR: a directory containing classes to be registered
     * @param string $sName
     *        When registering a function, this is the name of the function
     *        When registering a callable class, this is the class name
     *        When registering a callable directory, this is the full path to the directory
     * @param array|string $xOptions    The related options
     *
     * @return void
     * @throws SetupException
     */
    public function register(string $sType, string $sName, $xOptions = [])
    {
        $this->xPluginManager->registerCallable($sType, $sName, $xOptions);
    }

    /**
     * @return Factory
     */
    public function factory(): Factory
    {
        return $this->di()->getFactory();
    }

    /**
     * @return PsrFactory
     */
    public function psr(): PsrFactory
    {
        return $this->di()->getPsrFactory();
    }

    /**
     * @param string $sClassName The class name
     *
     * @return RequestFactory|null
     * @throws SetupException
     */
    public function request(string $sClassName = ''): ?RequestFactory
    {
        return $this->factory()->request($sClassName);
    }

    /**
     * Returns the Jaxon Javascript header and wrapper code to be printed into the page
     *
     * The javascript code returned by this function is dependent on the plugins
     * that are included and the functions and classes that are registered.
     *
     * @param bool $bIncludeJs    Also get the JS files
     * @param bool $bIncludeCss    Also get the CSS files
     *
     * @return string
     * @throws UriException
     */
    public function getScript(bool $bIncludeJs = false, bool $bIncludeCss = false): string
    {
        return $this->di()->getCodeGenerator()->getScript($bIncludeJs, $bIncludeCss);
    }

    /**
     * Return the javascript header code and file includes
     *
     * @return string
     */
    public function getJs(): string
    {
        return $this->di()->getCodeGenerator()->getJs();
    }

    /**
     * Return the CSS header code and file includes
     *
     * @return string
     */
    public function getCss(): string
    {
        return $this->di()->getCodeGenerator()->getCss();
    }

    /**
     * Determine if a call is a jaxon request or a page load request
     *
     * @return bool
     */
    public function canProcessRequest(): bool
    {
        return $this->di()->getRequestHandler()->canProcessRequest();
    }

    /**
     * If this is a jaxon request, call the requested PHP function, build the response and send it back to the browser
     *
     * This is the main server side engine for Jaxon.
     * It handles all the incoming requests, including the firing of events and handling of the response.
     * If your RequestURI is the same as your web page, then this function should be called before ANY
     * headers or HTML is output from your script.
     *
     * This function may exit after the request is processed, if the 'core.process.exit' option is set to true.
     *
     * @return void
     *
     * @throws RequestException
     * @see <Jaxon\Jaxon->canProcessRequest>
     */
    public function processRequest()
    {
        // Check to see if headers have already been sent out, in which case we can't do our job
        if(headers_sent($sFilename, $nLineNumber))
        {
            $sMessage = $this->xTranslator->trans('errors.output.already-sent', [
                'location' => $sFilename . ':' . $nLineNumber
            ]) . "\n" . $this->xTranslator->trans('errors.output.advice');
            throw new RequestException($sMessage);
        }

        $this->di()->getRequestHandler()->processRequest();

        if($this->xConfigManager->getOption('core.response.send'))
        {
            $this->sendResponse();
        }
    }

    /**
     * Prints the response to the output stream, thus sending the response to the browser
     *
     * @return void
     */
    public function sendResponse()
    {
        if(empty($sContent = $this->xResponseManager->getOutput()))
        {
            return;
        }
        if($this->di()->getRequest()->getMethod() === 'GET')
        {
            header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
            header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
            header("Cache-Control: no-cache, must-revalidate");
            header("Pragma: no-cache");
        }
        header('content-type: ' . $this->xResponseManager->getContentType());
        print $sContent;
        if($this->xConfigManager->getOption('core.process.exit'))
        {
            exit();
        }
    }

    /**
     * Get a registered response plugin
     *
     * @param string $sName    The name of the plugin
     *
     * @return ResponsePlugin|null
     */
    public function plugin(string $sName): ?ResponsePlugin
    {
        return $this->xPluginManager->getResponsePlugin($sName);
    }

    /**
     * Get a package instance
     *
     * @param string $sClassName    The package class name
     *
     * @return Package|null
     */
    public function package(string $sClassName): ?Package
    {
        return $this->di()->getPackageManager()->getPackage($sClassName);
    }

    /**
     * @return UploadHandler|null
     */
    public function upload(): ?UploadHandler
    {
        return $this->di()->getUploadHandler();
    }

    /**
     * @return CallbackManager
     */
    public function callback(): CallbackManager
    {
        return $this->di()->getCallbackManager();
    }

    /**
     * @return TemplateEngine
     */
    public function template(): TemplateEngine
    {
        return $this->di()->getTemplateEngine();
    }

    /**
     * @return App
     */
    public function app(): App
    {
        return $this->di()->getApp();
    }

    /**
     * @return ViewRenderer
     */
    public function view(): ViewRenderer
    {
        return $this->di()->getViewRenderer();
    }

    /**
     * @return Paginator
     */
    public function paginator(): Paginator
    {
        return $this->di()->getPaginator();
    }

    /**
     * @return DialogLibraryManager
     */
    public function dialog(): DialogLibraryManager
    {
        return $this->di()->getDialogLibraryManager();
    }

    /**
     * @return SessionInterface|null
     */
    public function session(): ?SessionInterface
    {
        return $this->di()->getSessionManager();
    }

    /**
     * @return void
     * @throws SetupException
     */
    public function reset()
    {
        self::$xInstance = null;
        self::$xContainer = null;
        // Need to register the default plugins.
        self::getInstance()->di()->getPluginManager()->registerPlugins();
    }
}