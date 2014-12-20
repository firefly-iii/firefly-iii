<?php
use Mockery as m;

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
        Artisan::call('migrate');
        $this->seed();


        //$this->
    }

    /**
     * @param $class
     *
     * @return m\MockInterface
     */
    public function mock($class)
    {
        $mock = Mockery::mock($class);

        $this->app->instance($class, $mock);

        return $mock;
    }

    static public function setupBeforeClass()
    {
        League\FactoryMuffin\Facade::loadFactories(__DIR__ . '/factories');
    }

    public function tearDown()
    {
        m::close();
    }
}