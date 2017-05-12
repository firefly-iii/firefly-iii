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

use FireflyIII\Support\Search\Search;
use FireflyIII\Support\Search\SearchInterface;
use Illuminate\Foundation\Application;
use Illuminate\Support\ServiceProvider;

/**
 * Class SearchServiceProvider
 *
 * @package FireflyIII\Providers
 */
class SearchServiceProvider extends ServiceProvider
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
            SearchInterface::class,
            function (Application $app) {
                /** @var Search $search */
                $search = app(Search::class);
                if ($app->auth->check()) {
                    $search->setUser(auth()->user());
                }

                return $search;
            }
        );
    }
}
