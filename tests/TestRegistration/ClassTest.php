<?php

namespace Jaxon\Tests\TestRegistration;

use Jaxon\Jaxon;
use Jaxon\Request\Plugin\CallableClass\CallableClassPlugin;
use Jaxon\Request\Plugin\CallableClass\CallableObject;
use Jaxon\Exception\SetupException;
use PHPUnit\Framework\TestCase;
use TheClass;

use function strlen;
use function file_get_contents;
use function jaxon;

require_once __DIR__ . '/../src/classes.php';

class ClassTest extends TestCase
{
    /**
     * @var CallableClassPlugin
     */
    protected $xPlugin;

    /**
     * @throws SetupException
     */
    public function setUp(): void
    {
        jaxon()->setOption('core.prefix.class', 'Jxn');

        jaxon()->register(Jaxon::CALLABLE_CLASS, 'Sample', __DIR__ . '/../src/sample.php');
        jaxon()->register(Jaxon::CALLABLE_CLASS, TheClass::class);

        $this->xPlugin = jaxon()->di()->getCallableClassPlugin();
    }

    /**
     * @throws SetupException
     */
    public function tearDown(): void
    {
        jaxon()->reset();
        parent::tearDown();
    }

    public function testPluginName()
    {
        $this->assertEquals(Jaxon::CALLABLE_CLASS, $this->xPlugin->getName());
    }

    public function testCallableClassClass()
    {
        $xSampleCallable = $this->xPlugin->getCallable('Sample');
        $xClassCallable = $this->xPlugin->getCallable(TheClass::class);
        // Test callables classes
        $this->assertEquals(CallableObject::class, get_class($xSampleCallable));
        $this->assertEquals(CallableObject::class, get_class($xClassCallable));
        // Check methods
        $this->assertTrue($xSampleCallable->hasMethod('myMethod'));
        $this->assertFalse($xSampleCallable->hasMethod('yourMethod'));
    }

    public function testCallableDirJsCode()
    {
        $this->assertEquals(32, strlen($this->xPlugin->getHash()));
        // $this->assertEquals('927202fb3aaa987a88d943939c3efe36', $this->xPlugin->getHash());
        $this->assertEquals(strlen(file_get_contents(__DIR__ . '/../src/js/class.js')),
            strlen($this->xPlugin->getScript()));
    }

    public function testClassNotFound()
    {
        // No callable for standard PHP functions.
        $this->expectException(SetupException::class);
        $this->xPlugin->getCallable('Simple');
    }

    /**
     * @throws SetupException
     */
    public function testCallableClassUnknownOption()
    {
        // Register a class method as a function, with unknown option
        jaxon()->register(Jaxon::CALLABLE_CLASS, 'TheClass', [
            'include' => __DIR__ . '/../src/classes.php',
            'functions' => [
                '*' => [
                    '__unknown' => 'unknown',
                ],
            ],
        ]);

        $xCallable = $this->xPlugin->getCallable('TheClass');
        $this->assertTrue($xCallable->hasMethod('theMethod'));
    }

    public function testCallableDirIncorrectOption()
    {
        // Register a function with incorrect option
        $this->expectException(SetupException::class);
        jaxon()->register(Jaxon::CALLABLE_CLASS, 'Sample', true);
    }

    public function testCallableDirIncorrectPath()
    {
        // Register a class with incorrect name
        $this->expectException(SetupException::class);
        jaxon()->register(Jaxon::CALLABLE_CLASS, 'Sam:ple');
    }
}