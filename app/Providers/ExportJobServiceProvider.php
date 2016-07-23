<?php
/**
 * ExportJobServiceProvider.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

declare(strict_types = 1);


namespace FireflyIII\Providers;

use FireflyIII\Exceptions\FireflyException;
use Illuminate\Foundation\Application;
use Illuminate\Support\ServiceProvider;


/**
 * Class ExportJobServiceProvider
 *
 * @package FireflyIII\Providers
 */
class ExportJobServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->exportJob();
        $this->importJob();

    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     *
     */
    private function exportJob()
    {

        $this->app->bind(
            'FireflyIII\Repositories\ExportJob\ExportJobRepositoryInterface',
            function (Application $app, array $arguments) {
                if (!isset($arguments[0]) && $app->auth->check()) {
                    return app('FireflyIII\Repositories\ExportJob\ExportJobRepository', [auth()->user()]);
                }
                if (!isset($arguments[0]) && !$app->auth->check()) {
                    throw new FireflyException('There is no user present.');
                }

                return app('FireflyIII\Repositories\ExportJob\ExportJobRepository', $arguments);
            }
        );
    }

    private function importJob()
    {
        $this->app->bind(
            'FireflyIII\Repositories\ImportJob\ImportJobRepositoryInterface',
            function (Application $app, array $arguments) {
                if (!isset($arguments[0]) && $app->auth->check()) {
                    return app('FireflyIII\Repositories\ImportJob\ImportJobRepository', [auth()->user()]);
                }
                if (!isset($arguments[0]) && !$app->auth->check()) {
                    throw new FireflyException('There is no user present.');
                }

                return app('FireflyIII\Repositories\ImportJob\ImportJobRepository', $arguments);
            }
        );
    }
}
