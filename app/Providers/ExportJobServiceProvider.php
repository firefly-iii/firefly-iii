<?php
/**
 * ExportJobServiceProvider.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types = 1);


namespace FireflyIII\Providers;

use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Repositories\ExportJob\ExportJobRepository;
use FireflyIII\Repositories\ExportJob\ExportJobRepositoryInterface;
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


    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->exportJob();
        $this->importJob();
    }

    /**
     *
     */
    private function exportJob()
    {
        $this->app->bind(
            ExportJobRepositoryInterface::class,
            function (Application $app) {
                /** @var ExportJobRepository $repository */
                $repository = app(ExportJobRepository::class);
                if ($app->auth->check()) {
                    $repository->setUser(auth()->user());
                }

                return $repository;
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
