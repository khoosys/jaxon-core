<?php

/**
 * CallableRepository.php - Jaxon callable object repository
 *
 * This class stores all the callable object already created.
 *
 * @package jaxon-core
 * @author Thierry Feuzeu <thierry.feuzeu@gmail.com>
 * @copyright 2019 Thierry Feuzeu <thierry.feuzeu@gmail.com>
 * @license https://opensource.org/licenses/BSD-3-Clause BSD 3-Clause License
 * @link https://github.com/jaxon-php/jaxon-core
 */

namespace Jaxon\Request\Support;

use Jaxon\Container\Container;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

use function explode;
use function strncmp;
use function strlen;
use function array_merge;
use function key_exists;

class CallableRepository
{
    /**
     * The DI container
     *
     * @var Container
     */
    protected $di;

    /**
     * The classes
     *
     * These are all the registered classes.
     *
     * @var array
     */
    protected $aClasses = [];

    /**
     * The namespaces
     *
     * These are all the namespaces found in registered directories.
     *
     * @var array
     */
    protected $aNamespaces = [];

    /**
     * The constructor
     *
     * @param Container         $di
     */
    public function __construct(Container $di)
    {
        $this->di = $di;
    }

    /**
     * Get a given class options from specified directory options
     *
     * @param string        $sClassName         The class name
     * @param array         $aClassOptions      The default class options
     * @param array         $aDirectoryOptions  The directory options
     *
     * @return array
     */
    public function makeClassOptions($sClassName, array $aClassOptions, array $aDirectoryOptions)
    {
        if(!key_exists('functions', $aClassOptions))
        {
            $aClassOptions['functions'] = [];
        }
        foreach(['separator', 'protected'] as $sName)
        {
            if(key_exists($sName, $aDirectoryOptions))
            {
                $aClassOptions[$sName] = $aDirectoryOptions[$sName];
            }
        }

        $aFunctionOptions = key_exists('classes', $aDirectoryOptions) ? $aDirectoryOptions['classes'] : [];
        foreach($aFunctionOptions as $sName => $xValue)
        {
            if($sName === '*' || strncmp($sClassName, $sName, strlen($sName)) === 0)
            {
                $aClassOptions['functions'] = array_merge($aClassOptions['functions'], $xValue);
            }
        }

        // This value will be used to compute hash
        if(!key_exists('timestamp', $aClassOptions))
        {
            $aClassOptions['timestamp'] = 0;
        }

        return $aClassOptions;
    }

    /**
     * Get all registered classes
     *
     * @return array
     */
    public function getClasses()
    {
        return $this->aClasses;
    }

    /**
     * Get all registered namespaces
     *
     * @return array
     */
    public function getNamespaces()
    {
        return $this->aNamespaces;
    }

    /**
     * Get the names of all registered classess
     *
     * @return array
     */
    public function getClassNames()
    {
        return array_keys($this->aClasses);
    }

    /**
     *
     * @param string        $sClassName         The class name
     * @param array         $aClassOptions      The default class options
     * @param array         $aDirectoryOptions  The directory options
     *
     * @return void
     */
    public function addClass($sClassName, array $aClassOptions, array $aDirectoryOptions = [])
    {
        $this->aClasses[$sClassName] = $this->makeClassOptions($sClassName, $aClassOptions, $aDirectoryOptions);
    }

    /**
     *
     * @param string        $sNamespace     The namespace
     * @param array|string  $aOptions       The associated options
     *
     * @return void
     */
    public function addNamespace($sNamespace, $aOptions)
    {
        $this->aNamespaces[$sNamespace] = $aOptions;
    }

    /**
     * Find the options associated with a registered class name
     *
     * @param string        $sClassName            The class name
     *
     * @return array|null
     */
    public function getClassOptions($sClassName)
    {
        if(!key_exists($sClassName, $this->aClasses))
        {
            // Class not found
            return null;
        }
        return $this->aClasses[$sClassName];
    }

    /**
     * Read classes from directories registered without namespaces
     *
     * @return void
     */
    public function parseDirectories(array $aDirectories)
    {
        // Browse directories without namespaces and read all the files.
        foreach($aDirectories as $sDirectory => $aOptions)
        {
            $itFile = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($sDirectory));
            // Iterate on dir content
            foreach($itFile as $xFile)
            {
                // skip everything except PHP files
                if(!$xFile->isFile() || $xFile->getExtension() != 'php')
                {
                    continue;
                }

                $sClassName = $xFile->getBasename('.php');
                $aClassOptions = ['timestamp' => $xFile->getMTime()];
                // No more classmap autoloading. The file will be included when needed.
                if(($aOptions['autoload']))
                {
                    $aClassOptions['include'] = $xFile->getPathname();
                }
                $this->addClass($sClassName, $aClassOptions, $aOptions);
            }
        }
    }

    /**
     * Read classes from directories registered with namespaces
     *
     * @return void
     */
    public function parseNamespaces(array $aNamespaces)
    {
        // Browse directories with namespaces and read all the files.
        $sDS = DIRECTORY_SEPARATOR;
        foreach($aNamespaces as $sNamespace => $aOptions)
        {
            $this->addNamespace($sNamespace, ['separator' => $aOptions['separator']]);

            // Iterate on dir content
            $sDirectory = $aOptions['directory'];
            $itFile = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($sDirectory));
            foreach($itFile as $xFile)
            {
                // skip everything except PHP files
                if(!$xFile->isFile() || $xFile->getExtension() != 'php')
                {
                    continue;
                }

                // Find the class path (the same as the class namespace)
                $sClassPath = $sNamespace;
                $sRelativePath = substr($xFile->getPath(), strlen($sDirectory));
                $sRelativePath = trim(str_replace($sDS, '\\', $sRelativePath), '\\');
                if($sRelativePath != '')
                {
                    $sClassPath .= '\\' . $sRelativePath;
                }

                $this->addNamespace($sClassPath, ['separator' => $aOptions['separator']]);

                $sClassName = $sClassPath . '\\' . $xFile->getBasename('.php');
                $aClassOptions = ['namespace' => $sNamespace, 'timestamp' => $xFile->getMTime()];
                $this->addClass($sClassName, $aClassOptions, $aOptions);
            }
        }
    }

    /**
     * Check if a callable object is created
     *
     * @param string        $sClassName            The class name of the callable object
     *
     * @return bool
     */
    public function hasCallableObject($sClassName)
    {
        return $this->di->has($sClassName);
    }

    /**
     * Find a callable object by class name
     *
     * @param string        $sClassName            The class name of the callable object
     *
     * @return CallableObject
     */
    public function getCallableObject($sClassName)
    {
        return $this->di->get($sClassName . '_CallableObject');
    }

    /**
     * Create a new callable object
     *
     * @param string        $sClassName            The class name of the callable object
     * @param array         $aOptions              The callable object options
     *
     * @return void
     */
    public function registerCallableObject($sClassName, array $aOptions)
    {
        // Make sure the registered class exists
        if(key_exists('include', $aOptions))
        {
            require_once($aOptions['include']);
        }
        if(!class_exists($sClassName))
        {
            return null;
        }
        // Register the callable object
        $this->di->registerCallableObject($sClassName, $aOptions);
    }
}
