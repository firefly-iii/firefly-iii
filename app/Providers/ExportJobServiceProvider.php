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

declare(strict_types=1);


namespace FireflyIII\Providers;

use FireflyIII\Repositories\ExportJob\ExportJobRepository;
use FireflyIII\Repositories\ExportJob\ExportJobRepositoryInterface;
use FireflyIII\Repositories\ImportJob\ImportJobRepository;
use FireflyIII\Repositories\ImportJob\ImportJobRepositoryInterface;
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
            ImportJobRepositoryInterface::class,
            function (Application $app) {
                /** @var ImportJobRepository $repository */
                $repository = app(ImportJobRepository::class);
                if ($app->auth->check()) {
                    $repository->setUser(auth()->user());
                }

                return $repository;
            }
        );
    }
}
