<?php

namespace FireflyIII\Providers;

use Illuminate\Support\ServiceProvider;
use Validator;
use FireflyIII\Validation\FireflyValidator;

/**
 * Class FireflyServiceProvider
 *
 * @package FireflyIII\Providers
 */
class FireflyServiceProvider extends ServiceProvider
{
    public function boot() {
        Validator::resolver(function($translator, $data, $rules, $messages)
        {
            return new FireflyValidator($translator, $data, $rules, $messages);
        });
    }
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

        $this->app->bind(
            'steam', function () {
            return new \FireflyIII\Support\Steam;
        }
        );
        $this->app->bind(
            'expandedform', function () {
            return new \FireflyIII\Support\ExpandedForm;
        }
        );

        // preferences
        $this->app->bind('FireflyIII\Repositories\Account\AccountRepositoryInterface', 'FireflyIII\Repositories\Account\AccountRepository');
        $this->app->bind('FireflyIII\Repositories\Journal\JournalRepositoryInterface', 'FireflyIII\Repositories\Journal\JournalRepository');
    }

}