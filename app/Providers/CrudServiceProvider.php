<?php
/**
 * CrudServiceProvider.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace FireflyIII\Providers;

use FireflyIII\Exceptions\FireflyException;
use Illuminate\Auth\AuthManager;
use Illuminate\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use Log;

/**
 * Class CrudServiceProvider
 *
 * @package FireflyIII\Providers
 */
class CrudServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->registerJournal();
        $this->registerAccount();
    }

    private function registerAccount()
    {

        $this->app->bind(
            'FireflyIII\Crud\Account\AccountCrudInterface',
            function (Application $app, array $arguments) {

                if (!isset($arguments[0]) && auth()->check()) {
                    return app('FireflyIII\Crud\Account\AccountCrud', [auth()->user()]);
                }
                if (!isset($arguments[0]) && !$app->auth->check()) {
                    throw new FireflyException('There is no user present.');
                }
                return app('FireflyIII\Crud\Account\AccountCrud', $arguments);
            }
        );
    }

    private function registerJournal()
    {
        $this->app->bind(
            'FireflyIII\Crud\Split\JournalInterface',
            function (Application $app, array $arguments) {
                if (!isset($arguments[0]) && $app->auth->check()) {
                    return app('FireflyIII\Crud\Split\Journal', [auth()->user()]);
                }
                if (!isset($arguments[0]) && !$app->auth->check()) {
                    throw new FireflyException('There is no user present.');
                }

                return app('FireflyIII\Crud\Split\Journal', $arguments);
            }
        );

    }
}
