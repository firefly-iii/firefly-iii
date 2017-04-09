<?php
/**
 * AccountServiceProvider.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);


namespace FireflyIII\Providers;

use FireflyIII\Repositories\Account\AccountRepository;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Account\AccountTasker;
use FireflyIII\Repositories\Account\AccountTaskerInterface;
use Illuminate\Foundation\Application;
use Illuminate\Support\ServiceProvider;

/**
 * Class AccountServiceProvider
 *
 * @package FireflyIII\Providers
 */
class AccountServiceProvider extends ServiceProvider
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
        $this->registerRepository();
        $this->registerTasker();
    }

    /**
     *
     */
    private function registerRepository()
    {
        $this->app->bind(
            AccountRepositoryInterface::class,
            function (Application $app) {
                /** @var AccountRepositoryInterface $repository */
                $repository = app(AccountRepository::class);

                if ($app->auth->check()) {
                    $repository->setUser(auth()->user());
                }

                return $repository;
            }
        );
    }

    /**
     *
     */
    private function registerTasker()
    {
        $this->app->bind(
            AccountTaskerInterface::class,
            function (Application $app) {
                /** @var AccountTaskerInterface $tasker */
                $tasker = app(AccountTasker::class);

                if ($app->auth->check()) {
                    $tasker->setUser(auth()->user());
                }

                return $tasker;
            }
        );
    }
}
