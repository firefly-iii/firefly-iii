<?php

use League\FactoryMuffin\Facade as f;


/**
 * Class TestCase
 */
class TestCase extends Illuminate\Foundation\Testing\TestCase
{
    /**
     * Creates the application.
     *
     * @return \Symfony\Component\HttpKernel\HttpKernelInterface
     */
    public function createApplication()
    {
        $unitTesting     = true;
        $testEnvironment = 'testing';

        return require __DIR__ . '/../../bootstrap/start.php';

    }

    public function setUp()
    {
        parent::setUp();
    }

    static public function setupBeforeClass()
    {
        //League\FactoryMuffin\Facade::loadFactories(__DIR__ . '/factories');
        f::loadFactories(__DIR__ . '/factories');
    }

    public function tearDown()
    {
        //m::close();
    }
}