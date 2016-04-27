<?php

namespace FireflyIII\Providers;

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
                if (!isset($arguments[0]) && $app->auth->check()) {
                    return app('FireflyIII\Repositories\Category\CategoryRepository', [$app->auth->user()]);
                } else {
                    if (!isset($arguments[0]) && !$app->auth->check()) {
                        throw new FireflyException('There is no user present.');
                    }
                }

                return app('FireflyIII\Repositories\Category\CategoryRepository', $arguments);
            }
        );

        $this->app->bind(
            'FireflyIII\Repositories\Category\SingleCategoryRepositoryInterface',
            function (Application $app, array $arguments) {
                if (!isset($arguments[0]) && $app->auth->check()) {
                    return app('FireflyIII\Repositories\Category\SingleCategoryRepository', [$app->auth->user()]);
                } else {
                    if (!isset($arguments[0]) && !$app->auth->check()) {
                        throw new FireflyException('There is no user present.');
                    }
                }

                return app('FireflyIII\Repositories\Category\SingleCategoryRepository', $arguments);
            }
        );

    }
}
