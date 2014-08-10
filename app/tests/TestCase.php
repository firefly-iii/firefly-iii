<?php

/**
 * Class TestCase
 */
class TestCase extends Illuminate\Foundation\Testing\TestCase
{

    /**
     * Creates the application.
     *
     * @return \Symfony\Component\HttpKernel\HttpKernelInterface
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     */
    public function createApplication()
    {
        /** @noinspection PhpUnusedLocalVariableInspection */
        $unitTesting = true;

        /** @noinspection PhpUnusedLocalVariableInspection */
        $testEnvironment = 'testing';

        return require __DIR__ . '/../../bootstrap/start.php';
    }

    /**
     * @param $class
     *
     * @return \Mockery\MockInterface
     */
    public function mock($class)
    {
        $mock = Mockery::mock($class);

        $this->app->instance($class, $mock);

        return $mock;
    }

}
