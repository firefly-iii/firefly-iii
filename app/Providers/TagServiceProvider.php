<?php
/**
 * TagServiceProvider.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);


namespace FireflyIII\Providers;

use FireflyIII\Repositories\Tag\TagRepository;
use FireflyIII\Repositories\Tag\TagRepositoryInterface;
use Illuminate\Foundation\Application;
use Illuminate\Support\ServiceProvider;

/**
 * Class TagServiceProvider
 *
 * @package FireflyIII\Providers
 */
class TagServiceProvider extends ServiceProvider
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
            TagRepositoryInterface::class,
            function (Application $app) {
                /** @var TagRepository $repository */
                $repository = app(TagRepository::class);

                if ($app->auth->check()) {
                    $repository->setUser(auth()->user());
                }

                return $repository;
            }
        );
    }
}
