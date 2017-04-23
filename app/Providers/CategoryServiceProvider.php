<?php
/**
 * CategoryServiceProvider.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);


namespace FireflyIII\Providers;

use FireflyIII\Repositories\Category\CategoryRepository;
use FireflyIII\Repositories\Category\CategoryRepositoryInterface;
use Illuminate\Foundation\Application;
use Illuminate\Support\ServiceProvider;

/**
 * Class CategoryServiceProvider
 *
 * @package FireflyIII\Providers
 */
class CategoryServiceProvider extends ServiceProvider
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
            CategoryRepositoryInterface::class,
            function (Application $app) {
                /** @var CategoryRepository $repository */
                $repository = app(CategoryRepository::class);
                if ($app->auth->check()) {
                    $repository->setUser(auth()->user());
                }

                return $repository;
            }
        );

    }
}
