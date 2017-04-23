<?php
/**
 * BillServiceProvider.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);


namespace FireflyIII\Providers;

use FireflyIII\Repositories\Bill\BillRepository;
use FireflyIII\Repositories\Bill\BillRepositoryInterface;
use Illuminate\Foundation\Application;
use Illuminate\Support\ServiceProvider;

/**
 * Class BillServiceProvider
 *
 * @package FireflyIII\Providers
 */
class BillServiceProvider extends ServiceProvider
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
            BillRepositoryInterface::class,
            function (Application $app) {
                /** @var BillRepositoryInterface $repository */
                $repository = app(BillRepository::class);

                if ($app->auth->check()) {
                    $repository->setUser(auth()->user());
                }

                return $repository;
            }
        );
    }
}
