<?php

namespace FireflyIII\Providers;

use Auth;
use FireflyIII\Exceptions\FireflyException;
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
            'FireflyIII\Repositories\Category\CategoryRepositoryInterface',
            function (Application $app, array $arguments) {
                if (!isset($arguments[0]) && Auth::check()) {
                    return app('FireflyIII\Repositories\Category\CategoryRepository', [Auth::user()]);
                } else {
                    if (!isset($arguments[0]) && !Auth::check()) {
                        throw new FireflyException('There is no user present.');
                    }
                }

                return app('FireflyIII\Repositories\Category\CategoryRepository', $arguments);
            }
        );
    }
}
