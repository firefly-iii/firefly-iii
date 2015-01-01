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
        $testEnvironment = 'testingInMemory';


        return require __DIR__ . '/../../bootstrap/start.php';

    }

    public function setUp()
    {
        parent::setUp();
        $this->prepareForTests();

    }

    static public function setupBeforeClass()
    {
        f::loadFactories(__DIR__ . '/factories');
    }

    public function tearDown()
    {
        //m::close();
    }

    /**
     * Migrates the database and set the mailer to 'pretend'.
     * This will cause the tests to run quickly.
     *
     */
    private function prepareForTests()
    {
        Artisan::call('migrate');
        Mail::pretend(true);
    }
}