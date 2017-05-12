<?php
/**
 * PiggyBankServiceProvider.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);


namespace FireflyIII\Providers;

use FireflyIII\Repositories\PiggyBank\PiggyBankRepository;
use FireflyIII\Repositories\PiggyBank\PiggyBankRepositoryInterface;
use Illuminate\Foundation\Application;
use Illuminate\Support\ServiceProvider;


/**
 * Class PiggyBankServiceProvider
 *
 * @package FireflyIII\Providers
 */
class PiggyBankServiceProvider extends ServiceProvider
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
        $this->app->bind(
            PiggyBankRepositoryInterface::class,
            function (Application $app) {
                /** @var PiggyBankRepository $repository */
                $repository = app(PiggyBankRepository::class);
                if ($app->auth->check()) {
                    $repository->setUser(auth()->user());
                }

                return $repository;
            }
        );
    }
}
