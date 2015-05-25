<?php

namespace FireflyIII\Providers;


use Illuminate\Support\ServiceProvider;

/**
 * Class TestingServiceProvider
 *
 * @codeCoverageIgnore
 * @package FireflyIII\Providers
 */
class TestingServiceProvider extends ServiceProvider
{

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        if ($this->app->environment() == 'testing') {
            $this->app['config']['session.driver'] = 'native';
        }
    }

}
