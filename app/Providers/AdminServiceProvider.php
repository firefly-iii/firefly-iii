<?php
/**
 * AdminServiceProvider.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace FireflyIII\Providers;

use FireflyIII\Repositories\LinkType\LinkTypeRepository;
use FireflyIII\Repositories\LinkType\LinkTypeRepositoryInterface;
use Illuminate\Foundation\Application;
use Illuminate\Support\ServiceProvider;


class AdminServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {


    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->linkType();
    }

    /**
     *
     */
    private function linkType()
    {
        $this->app->bind(
            LinkTypeRepositoryInterface::class,
            function (Application $app) {
                /** @var LinkTypeRepository $repository */
                $repository = app(LinkTypeRepository::class);
                if ($app->auth->check()) {
                    $repository->setUser(auth()->user());
                }

                return $repository;
            }
        );
    }

}