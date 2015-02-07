<?php

namespace FireflyIII\Providers;

use Illuminate\Support\ServiceProvider;

class FireflyServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->bind(
            'preferences', function () {
            return new \FireflyIII\Support\Preferences;
        }
        );
        $this->app->bind(
            'navigation', function () {
            return new \FireflyIII\Support\Navigation;
        }
        );
        $this->app->bind(
            'amount', function () {
            return new \FireflyIII\Support\Amount;
        }
        );
    }

}