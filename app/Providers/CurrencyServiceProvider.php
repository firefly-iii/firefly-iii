<?php
/**
 * CurrencyServiceProvider.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);


namespace FireflyIII\Providers;

use FireflyIII\Repositories\Currency\CurrencyRepository;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use Illuminate\Foundation\Application;
use Illuminate\Support\ServiceProvider;

/**
 * Class CurrencyServiceProvider
 *
 * @package FireflyIII\Providers
 */
class CurrencyServiceProvider extends ServiceProvider
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
            CurrencyRepositoryInterface::class,
            function (Application $app) {
                /** @var CurrencyRepository $repository */
                $repository = app(CurrencyRepository::class);
                if ($app->auth->check()) {
                    $repository->setUser(auth()->user());
                }

                return $repository;
            }
        );
    }

}
